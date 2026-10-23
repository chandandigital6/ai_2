<?php

use App\Jobs\Extensions\SendPush;
use App\Models\Business;
use App\Models\BusinessUser;
use App\Models\GrowyaChatMessage;
use App\Models\GrowyaChatThread;
use App\Models\Notification;
use App\Models\NotificationRecipient;
use App\Models\Order;
use App\Models\Product;
use App\Models\Service;
use App\Models\ServiceEnquiry;
use App\Models\User;
use App\Models\UserDevice;
use App\Services\Extensions\FcmService;
use App\Services\GrowyaNotificationService;
use Database\Seeders\BusinessPermissionSeeder;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Spatie\Permission\Models\Permission;

beforeEach(function () {
    Queue::fake();
    Http::preventStrayRequests();
    config(['growya_extensions.fcm.enabled' => true,
        'growya_extensions.fcm.web' => ['enabled' => true, 'api_key' => 'public-key', 'project_id' => 'demo-project', 'sender_id' => '123456', 'app_id' => '1:123456:web:demo', 'vapid_key' => 'public-vapid']]);
    $this->seed(BusinessPermissionSeeder::class);
    $this->owner = User::factory()->create(['status' => 'active']);
    $this->buyer = User::factory()->create(['status' => 'active']);
    $this->shop = Business::withoutEvents(fn () => Business::create(['owner_id' => $this->owner->id, 'name' => 'Push Demo Shop', 'slug' => 'push-demo', 'status' => 'active', 'verification_status' => 'approved']));
    $this->thread = GrowyaChatThread::create(['business_id' => $this->shop->id, 'customer_id' => $this->buyer->id, 'context_type' => 'general', 'context_id' => 0]);
    $this->device = UserDevice::create(['user_id' => $this->owner->id, 'platform' => 'web', 'fcm_token' => str_repeat('o', 40), 'token_hash' => hash('sha256', str_repeat('o', 40)), 'is_active' => true]);
});

it('registers a web token through the session route without exposing it', function () {
    $token = str_repeat('w', 40);
    $this->actingAs($this->buyer)->postJson('/my-notifications/push/register', ['fcm_token' => $token, 'platform' => 'android'])
        ->assertCreated()->assertJsonPath('data.platform', 'web')->assertDontSee($token);
    $this->assertDatabaseHas('user_devices', ['user_id' => $this->buyer->id, 'token_hash' => hash('sha256', $token), 'platform' => 'web', 'is_active' => true]);
    expect(session('growya_web_device_id'))->toBeGreaterThan(0);
});

it('requires login and complete browser settings for registration', function () {
    $this->postJson('/my-notifications/push/register', ['fcm_token' => str_repeat('x', 40)])->assertUnauthorized();
    config(['growya_extensions.fcm.web.vapid_key' => null]);
    $this->actingAs($this->buyer)->postJson('/my-notifications/push/register', ['fcm_token' => str_repeat('x', 40)])->assertStatus(503);
});

it('exposes only public config and serves the worker without a login', function () {
    config(['growya_extensions.fcm.credentials' => '/private/SECRET-service-account.json']);
    $response = $this->get('/firebase-messaging-sw.js')->assertOk()->assertHeader('Content-Type', 'application/javascript; charset=UTF-8')
        ->assertSee('notificationclick')->assertSee('onBackgroundMessage')->assertDontSee('SECRET');
    $this->actingAs($this->owner)->getJson('/my-notifications/push/config')->assertOk()->assertJsonPath('enabled', true)->assertJsonPath('firebase.projectId', 'demo-project')->assertDontSee('SECRET')->assertDontSee($this->device->fcm_token);
    $page = $this->get('/my-notifications')->assertOk()->assertSee('Enable browser alerts')->assertSee('Send me a test');
    if (getenv('NOTIFY_QA_DIR')) {
        file_put_contents(getenv('NOTIFY_QA_DIR').'/notifications.html', $page->getContent());
        file_put_contents(getenv('NOTIFY_QA_DIR').'/worker.js', $response->getContent());
    }
});

it('disables only the current browser session and revokes it on logout', function () {
    $second = UserDevice::create(['user_id' => $this->owner->id, 'platform' => 'android', 'fcm_token' => str_repeat('a', 40), 'is_active' => true]);
    $this->actingAs($this->owner)->withSession(['growya_web_device_id' => $this->device->id])->postJson('/my-notifications/push/disable')->assertOk();
    expect($this->device->fresh()->is_active)->toBeFalse()->and($second->fresh()->is_active)->toBeTrue();
    $this->device->update(['is_active' => true]);
    $this->withSession(['growya_web_device_id' => $this->device->id])->post('/logout')->assertRedirect();
    expect($this->device->fresh()->is_active)->toBeFalse()->and($second->fresh()->is_active)->toBeTrue();
});

it('queues a test only for a signed in user with a registered browser', function () {
    $this->actingAs($this->buyer)->postJson('/my-notifications/push/test')->assertUnprocessable();
    $this->actingAs($this->owner)->withSession(['growya_web_device_id' => $this->device->id])->postJson('/my-notifications/push/test')->assertOk();
    expect(Notification::where('type', 'push_test')->count())->toBe(1);
    Queue::assertPushed(SendPush::class, 1);
});

it('opens the correct chat and marks only the owned notification and delivery', function () {
    app(GrowyaNotificationService::class)->send([$this->owner->id], 'chat_message', 'Buyer', 'Hello', '/messages/'.$this->thread->id, $this->shop->id);
    $recipient = NotificationRecipient::firstOrFail();
    $delivery = DB::table('growya_push_deliveries')->first();
    DB::table('growya_push_deliveries')->where('id', $delivery->id)->update(['status' => 'accepted']);
    $this->actingAs($this->buyer)->get('/my-notifications/'.$recipient->id.'/visit')->assertNotFound();
    $this->actingAs($this->owner)->get('/my-notifications/'.$recipient->id.'/visit?delivery='.$delivery->id)->assertRedirect('/messages/'.$this->thread->id);
    expect($recipient->fresh()->opened_at)->not->toBeNull();
    $this->assertDatabaseHas('growya_push_deliveries', ['id' => $delivery->id, 'status' => 'opened']);
});

it('does not redirect a notification to an external host', function () {
    $n = Notification::create(['type' => 'test', 'title' => 'Test', 'message' => 'Test', 'action_url' => '/\\evil.test', 'target_type' => 'users', 'status' => 'sent']);
    $r = NotificationRecipient::create(['notification_id' => $n->id, 'user_id' => $this->owner->id, 'status' => 'sent']);
    $this->actingAs($this->owner)->get('/my-notifications/'.$r->id.'/visit')->assertRedirect('/my-notifications');
});

it('saves the chat message and queues its owner alert with a direct conversation target', function () {
    $this->actingAs($this->buyer)->post('/messages/'.$this->thread->id, ['body' => 'Please confirm availability'])->assertSessionHasNoErrors()->assertRedirect('/messages/'.$this->thread->id);
    expect(GrowyaChatMessage::where('thread_id', $this->thread->id)->value('body'))->toBe('Please confirm availability');
    $n = Notification::where('type', 'chat_message')->firstOrFail();
    expect($n->action_url)->toBe('/messages/'.$this->thread->id)->and($n->title)->toBe($this->buyer->name);
    expect($n->notification_recipients()->pluck('user_id')->all())->toBe([$this->owner->id]);
    Queue::assertPushed(SendPush::class, 1);
    $page = $this->actingAs($this->owner)->get('/messages/'.$this->thread->id)->assertOk()->assertSee('Please confirm availability')->assertSee('growya-ui/chat.js');
    if (getenv('NOTIFY_QA_DIR')) {
        file_put_contents(getenv('NOTIFY_QA_DIR').'/chat.html', $page->getContent());
    }
});

it('marks only returned chat messages as read when a poll has a backlog', function () {
    for ($i = 0; $i < 51; $i++) {
        GrowyaChatMessage::create(['thread_id' => $this->thread->id, 'sender_id' => $this->buyer->id, 'body' => 'Message '.$i]);
    }
    $this->actingAs($this->owner)->getJson('/messages/'.$this->thread->id.'/poll?after=0')->assertOk()->assertJsonCount(50, 'messages');
    expect(GrowyaChatMessage::whereNotNull('read_at')->count())->toBe(50)->and(GrowyaChatMessage::latest('id')->value('read_at'))->toBeNull();
    $outside = User::factory()->create(['status' => 'active']);
    $this->actingAs($outside)->getJson('/messages/'.$this->thread->id.'/poll')->assertForbidden();
});

it('notifies assigned chat staff only while their business read permission is active', function () {
    $staff = User::factory()->create(['status' => 'active']);
    $member = BusinessUser::create(['business_id' => $this->shop->id, 'user_id' => $staff->id, 'role' => 'staff', 'is_active' => true]);
    $this->thread->update(['assigned_to' => $staff->id]);
    $this->actingAs($this->buyer)->post('/messages/'.$this->thread->id, ['body' => 'First message'])->assertSessionHasNoErrors();
    expect(NotificationRecipient::pluck('user_id')->all())->toBe([$this->owner->id]);
    $member->permissions()->sync(Permission::where('name', 'chats.view')->pluck('id'));
    $this->post('/messages/'.$this->thread->id, ['body' => 'Second message'])->assertSessionHasNoErrors();
    $n = Notification::latest('id')->firstOrFail();
    expect($n->notification_recipients()->orderBy('user_id')->pluck('user_id')->all())->toBe([$this->owner->id, $staff->id]);
});

it('rejects an Android client JSON as server credentials without printing its contents', function () {
    $path = tempnam(sys_get_temp_dir(), 'growya-wrong-json');
    file_put_contents($path, json_encode(['type' => 'android-client', 'private_key' => 'DO-NOT-PRINT-THIS']));
    try {
        config(['growya_extensions.fcm.credentials' => $path]);
        expect(Artisan::call('growya:push-check'))->toBe(1);
        expect(Artisan::output())->toContain('MISSING OR INVALID')->not->toContain('DO-NOT-PRINT-THIS');
        Http::assertNothingSent();
    } finally {
        unlink($path);
    }
});

it('queues owner push alerts for orders and product and service enquiries', function () {
    Order::create(['business_id' => $this->shop->id, 'customer_id' => $this->buyer->id, 'order_number' => 'PUSH-1', 'customer_name' => 'Buyer', 'customer_phone' => '9876543210', 'address' => 'Kanpur', 'subtotal' => 100, 'discount_amount' => 0, 'tax_amount' => 0, 'total_amount' => 100, 'status' => 'pending', 'payment_method' => 'cod', 'payment_status' => 'pending']);
    $p = Product::create(['business_id' => $this->shop->id, 'name' => 'Demo item', 'slug' => 'demo-item', 'price' => 100, 'stock_quantity' => 5, 'stock_status' => 'in_stock', 'status' => 'active']);
    $this->actingAs($this->buyer)->post('/products/'.$this->shop->id.'/'.$p->id.'/enquiry', ['name' => 'Buyer', 'phone' => '9876543210', 'quantity' => 1])->assertSessionHasNoErrors()->assertRedirect();
    $service = Service::create(['business_id' => $this->shop->id, 'name' => 'Demo service', 'slug' => 'demo-service', 'price' => 100, 'status' => 'active']);
    ServiceEnquiry::create(['service_id' => $service->id, 'business_id' => $this->shop->id, 'customer_name' => 'Buyer', 'phone' => '9876543210', 'status' => 'new']);
    foreach (['new_order', 'product_enquiry', 'service_enquiry'] as $type) {
        $n = Notification::where('type', $type)->firstOrFail();
        expect($n->notification_recipients()->pluck('user_id')->all())->toBe([$this->owner->id]);
    }
    Queue::assertPushed(SendPush::class, 3);
});

it('sends data-only web push and native notification payloads with routing IDs', function () {
    $key = openssl_pkey_new(['private_key_bits' => 2048, 'private_key_type' => OPENSSL_KEYTYPE_RSA]);
    openssl_pkey_export($key, $pem);
    $path = tempnam(sys_get_temp_dir(), 'growya-push-test');
    file_put_contents($path, json_encode(['type' => 'service_account', 'project_id' => 'demo-project', 'client_email' => 'push@example.test', 'private_key' => $pem]));
    try {
        config(['growya_extensions.fcm.credentials' => $path]);
        Http::fake(['oauth2.googleapis.com/token' => Http::response(['access_token' => 'fake-access'], 200), 'fcm.googleapis.com/*' => Http::response(['name' => 'projects/demo/messages/1'], 200)]);
        app(GrowyaNotificationService::class)->send([$this->owner->id], 'chat_message', 'Buyer', 'Hi', '/messages/'.$this->thread->id, $this->shop->id);
        $id = DB::table('growya_push_deliveries')->value('id');
        (new SendPush($id))->handle(app(FcmService::class));
        Http::assertSent(fn ($request) => str_contains($request->url(), 'messages:send') && ! isset($request['message']['notification'])
            && $request['message']['data']['thread_id'] === (string) $this->thread->id && $request['message']['data']['type'] === 'chat_message'
            && str_contains($request['message']['data']['url'], '/visit') && $request['message']['data']['title'] === 'Buyer');
        $result = app(FcmService::class)->send(str_repeat('a', 40), ['title' => 'Native', 'body' => 'Hello'], ['order_id' => 12], 'android');
        expect($result['ok'])->toBeTrue();
        Http::assertSent(fn ($request) => isset($request['message']['notification']) && $request['message']['android']['priority'] === 'high' && $request['message']['data']['order_id'] === '12');
    } finally {
        unlink($path);
    }
});
