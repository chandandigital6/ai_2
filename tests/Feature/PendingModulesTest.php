<?php

use App\Jobs\Extensions\GenerateAi;
use App\Jobs\Extensions\PublishSocial;
use App\Jobs\Extensions\SendPush;
use App\Models\AiGeneration;
use App\Models\AiPlan;
use App\Models\AiPlanRequest;
use App\Models\Banner;
use App\Models\Business;
use App\Models\BusinessUser;
use App\Models\Cart;
use App\Models\Notification;
use App\Models\NotificationRecipient;
use App\Models\Order;
use App\Models\Product;
use App\Models\Review;
use App\Models\SocialAccount;
use App\Models\SocialPost;
use App\Models\SocialSchedule;
use App\Models\User;
use App\Models\UserDevice;
use App\Services\Extensions\AiProvider;
use App\Services\Extensions\AiWalletService;
use App\Services\Extensions\AnalyticsService;
use App\Services\Extensions\CommerceService;
use App\Services\Extensions\FcmService;
use App\Services\Extensions\JournalService;
use App\Services\Extensions\MetaService;
use App\Services\Extensions\RazorpayService;
use App\Services\Extensions\SocialScheduler;
use Carbon\CarbonImmutable;
use Database\Seeders\BusinessPermissionSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    config(['growya_extensions.ai.enabled' => false, 'growya_extensions.ai.key' => null]);
    $this->seed(BusinessPermissionSeeder::class);
    Role::findOrCreate('super_admin', 'web');
    $this->actor = User::factory()->create(['status' => 'active', 'phone' => '9876543210']);
    $this->shop = Business::withoutEvents(fn () => Business::create(['owner_id' => $this->actor->id, 'name' => 'Extension Store', 'slug' => 'extension-store', 'status' => 'active', 'verification_status' => 'approved']));
    $this->outside = User::factory()->create(['status' => 'active']);
    Queue::fake();
    Http::preventStrayRequests();
});
function extensionOrder($test, array $values = []): Order
{
    return Order::withoutEvents(fn () => Order::create(array_merge(['business_id' => $test->shop->id, 'customer_id' => $test->actor->id, 'order_number' => 'EXT-'.Str::random(12), 'customer_name' => 'Buyer', 'customer_phone' => '9876543210', 'subtotal' => '100.00', 'discount_amount' => '0.00', 'tax_amount' => '0.00', 'total_amount' => '100.00', 'payment_method' => 'razorpay', 'payment_status' => 'pending', 'status' => 'shipped'], $values)));
}

it('protects new tenant reads from outsiders', function (string $suffix) {
    Sanctum::actingAs($this->outside, ['growya']);
    $this->getJson('/api/v1/businesses/'.$this->shop->id.'/'.$suffix)->assertForbidden();
})->with(['ai', 'ai/credits', 'social/accounts', 'social/posts', 'social/schedules', 'commerce-policy', 'accounting/accounts', 'accounting/journals', 'accounting/reports', 'analytics/daily', 'gateway-payments']);

it('renders every business workspace', function (string $section) {
    $this->actingAs($this->actor)->get('/businesses/'.$this->shop->id.'/workspace/'.$section)->assertOk();
})->with(['ai', 'social', 'commerce', 'accounting', 'analytics', 'delivery', 'payments']);
it('renders each admin tool', function (string $kind) {
    $this->actor->assignRole('super_admin');
    $this->actingAs($this->actor)->get('/admin/module-tools/'.$kind)->assertOk();
})->with(['banners', 'settings', 'festivals', 'reports', 'audit']);

it('keeps AI credit changes idempotent and rejects overdrafts', function () {
    $wallet = app(AiWalletService::class);
    $wallet->change($this->shop->id, 20, 'grant', 'g1');
    $wallet->change($this->shop->id, 20, 'grant', 'g1');
    $wallet->change($this->shop->id, 10, 'debit', 'd1');
    $wallet->change($this->shop->id, 10, 'refund', 'r1');
    $wallet->change($this->shop->id, 10, 'refund', 'r1');
    expect($wallet->balance($this->shop->id)['available_credits'])->toBe(20);
    expect(fn () => $wallet->change($this->shop->id, 21, 'debit', 'd2'))->toThrow(ValidationException::class);
    expect(DB::table('ai_credit_transactions')->count())->toBe(3);
});
it('reserves generation credits once and refunds a failed provider once', function () {
    config(['growya_extensions.ai.enabled' => true, 'growya_extensions.ai.key' => 'test']);
    app(AiWalletService::class)->change($this->shop->id, 20, 'grant', 'initial');
    $plan = AiPlan::create(['name' => 'Refund test', 'credits' => 20, 'valid_days' => 30, 'text_model' => 'gpt-4.1-mini', 'text_credits' => 1, 'image_credits' => 10]);
    AiPlanRequest::create(['business_id' => $this->shop->id, 'ai_plan_id' => $plan->id,
        'request_key' => 'test-purchase', 'status' => 'approved', 'expires_at' => now()->addDays(30),
        'plan_snapshot' => ['name' => 'Refund test', 'credits' => 20, 'text_model' => 'gpt-4.1-mini', 'text_credits' => 1, 'max_references' => 0, 'max_prompts' => 0]]);
    Sanctum::actingAs($this->actor, ['growya']);
    $payload = ['generation_type' => 'text', 'prompt' => 'Write an opening caption', 'request_key' => 'same'];
    $url = '/api/v1/businesses/'.$this->shop->id.'/ai';
    $a = $this->postJson($url, $payload)->assertStatus(202);
    $this->postJson($url, $payload)->assertStatus(202);
    expect(AiGeneration::count())->toBe(1);
    expect(app(AiWalletService::class)->balance($this->shop->id)['available_credits'])->toBe(19);
    Http::fake(['api.openai.com/*' => Http::response(['error' => ['message' => 'No']], 500)]);
    $job = new GenerateAi($a->json('data.id'));
    $job->handle(app(AiProvider::class), app(AiWalletService::class));
    $job->failed(null);
    expect(app(AiWalletService::class)->balance($this->shop->id)['available_credits'])->toBe(20);
    expect(AiGeneration::first()->status)->toBe('failed');
});
it('blocks credit grants from business owners', function () {
    Sanctum::actingAs($this->actor, ['growya']);
    $this->postJson('/api/v1/admin/businesses/'.$this->shop->id.'/ai/credits', ['credits' => 500, 'request_key' => 'bad'])->assertForbidden();
});
it('does not call unconfigured providers', function () {
    Sanctum::actingAs($this->actor, ['growya']);
    $this->postJson('/api/v1/businesses/'.$this->shop->id.'/ai', ['generation_type' => 'text', 'prompt' => 'Test prompt', 'request_key' => 'k'])->assertStatus(503);
    $this->postJson('/api/v1/auth/otp/send', ['phone' => '9876543210'])->assertStatus(503);
    Http::assertNothingSent();
});

it('persists failed OTP attempts and consumes a correct code only once', function () {
    DB::table('otp_codes')->insert(['phone' => $this->actor->phone, 'otp' => Hash::make('123456'), 'purpose' => 'login', 'expires_at' => now()->addMinutes(5), 'attempts' => 0, 'created_at' => now(), 'updated_at' => now()]);
    $url = '/api/v1/auth/otp/verify';
    $d = ['phone' => $this->actor->phone, 'code' => '000000', 'device_name' => 'test'];
    $this->postJson($url, $d)->assertUnprocessable();
    expect(DB::table('otp_codes')->value('attempts'))->toBe(1);
    $d['code'] = '123456';
    $this->postJson($url, $d)->assertOk()->assertJsonStructure(['data' => ['token', 'user']]);
    $this->postJson($url, $d)->assertUnprocessable();
});
it('does not bypass two factor authentication with phone OTP', function () {
    $this->actor->forceFill(['two_factor_secret' => encrypt('secret'), 'two_factor_confirmed_at' => now()])->save();
    DB::table('otp_codes')->insert(['phone' => $this->actor->phone, 'otp' => Hash::make('123456'), 'purpose' => 'login', 'expires_at' => now()->addMinutes(5), 'attempts' => 0, 'created_at' => now(), 'updated_at' => now()]);
    $this->postJson('/api/v1/auth/otp/verify', ['phone' => $this->actor->phone, 'code' => '123456', 'device_name' => 'test'])->assertUnprocessable();
    expect(DB::table('otp_codes')->value('verified_at'))->toBeNull();
});
it('registers devices without exposing tokens and prevents foreign deletion', function () {
    Sanctum::actingAs($this->actor, ['growya']);
    $token = str_repeat('a', 60);
    $r = $this->postJson('/api/v1/devices', ['fcm_token' => $token, 'platform' => 'android'])->assertCreated();
    expect($r->getContent())->not->toContain($token);
    $this->postJson('/api/v1/devices', ['fcm_token' => $token, 'platform' => 'android'])->assertCreated();
    expect(DB::table('user_devices')->where('is_active', true)->count())->toBe(1);
    Sanctum::actingAs($this->outside, ['growya']);
    $this->deleteJson('/api/v1/devices/'.$r->json('data.id'))->assertNotFound();
});

it('records own history and rechecks public resource visibility for shares', function () {
    Sanctum::actingAs($this->actor, ['growya']);
    $this->postJson('/api/v1/activity-events', ['resource_type' => 'business', 'resource_id' => $this->shop->id, 'event_type' => 'view'])->assertCreated();
    $this->getJson('/api/v1/recently-viewed')->assertOk()->assertJsonPath('data.total', 1);
    $share = $this->postJson('/api/v1/share-links', ['resource_type' => 'business', 'resource_id' => $this->shop->id])->assertCreated();
    $this->shop->updateQuietly(['status' => 'inactive']);
    $this->getJson('/api/v1/share-links/'.$share->json('data.code'))->assertNotFound();
});
it('rebuilds analytics without double counting', function () {
    DB::table('activity_events')->insert(['business_id' => $this->shop->id, 'event_type' => 'view', 'created_at' => now(), 'updated_at' => now()]);
    app(AnalyticsService::class)->aggregate(today()->toDateString());
    app(AnalyticsService::class)->aggregate(today()->toDateString());
    expect(DB::table('business_daily_analytics')->count())->toBe(1);
    expect(DB::table('business_daily_analytics')->value('views'))->toBe(1);
});
it('keeps unapproved review photos private', function () {
    Storage::fake('local');
    $review = Review::withoutEvents(fn () => Review::create(['business_id' => $this->shop->id, 'user_id' => $this->actor->id, 'rating' => 5, 'review' => 'Excellent service', 'status' => 'approved', 'helpful_count' => 0]));
    Sanctum::actingAs($this->actor, ['growya']);
    $png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+a9xkAAAAASUVORK5CYII=');
    $r = $this->postJson('/api/v1/reviews/'.$review->id.'/photos', ['images' => [UploadedFile::fake()->createWithContent('photo.png', $png)]])->assertCreated();
    expect($review->fresh()->status)->toBe('pending');
    Sanctum::actingAs($this->outside, ['growya']);
    $this->get('/api/v1/review-photos/'.$r->json('data.images.0.id'))->assertNotFound();
});

it('rejects foreign social accounts in a post', function () {
    Sanctum::actingAs($this->actor, ['growya']);
    $other = Business::withoutEvents(fn () => Business::create(['owner_id' => $this->outside->id, 'name' => 'Other', 'slug' => 'other-social', 'status' => 'active', 'verification_status' => 'approved']));
    $a = SocialAccount::create(['business_id' => $other->id, 'platform' => 'facebook', 'external_id' => '1234', 'access_token' => 'private', 'connection_status' => 'connected']);
    $this->postJson('/api/v1/businesses/'.$this->shop->id.'/social/posts', ['caption' => 'Hello', 'account_ids' => [$a->id], 'publish_mode' => 'draft', 'request_key' => 'p'])->assertUnprocessable();
});
it('validates OAuth state before making external requests', function () {
    $this->get('/integrations/meta/callback?state=wrong&code=bad')->assertStatus(422);
    Http::assertNothingSent();
});
it('computes weekly schedules in the chosen timezone', function () {
    $s = new SocialSchedule(['frequency' => 'weekly', 'run_time' => '09:00', 'days_of_week' => [1]]);
    $s->forceFill(['timezone' => 'Asia/Kolkata']);
    $after = CarbonImmutable::parse('2026-10-19 04:00:00', 'UTC');
    expect(app(SocialScheduler::class)->next($s, $after)->setTimezone('UTC')->format('Y-m-d H:i'))->toBe('2026-10-26 03:30');
});
it('publishes a queued Facebook post once', function () {
    config(['growya_extensions.meta.version' => 'v99.0', 'growya_extensions.meta.app_id' => 'test', 'growya_extensions.meta.secret' => 'secret', 'growya_extensions.meta.redirect_uri' => 'https://example.test/integrations/meta/callback']);
    $a = SocialAccount::create(['business_id' => $this->shop->id, 'platform' => 'facebook', 'external_id' => '123', 'access_token' => 'test-token', 'connection_status' => 'connected']);
    $p = SocialPost::create(['business_id' => $this->shop->id, 'created_by' => $this->actor->id, 'caption' => 'Hello there', 'post_type' => 'text', 'publish_mode' => 'now', 'scheduled_at' => now()->subMinute(), 'overall_status' => 'queued']);
    $target = $p->social_post_platforms()->create(['social_account_id' => $a->id, 'status' => 'queued']);
    Http::fake(['graph.facebook.com/*' => Http::response(['id' => '123_456'], 200)]);
    $job = new PublishSocial($target->id);
    $job->handle(app(MetaService::class));
    $job->handle(app(MetaService::class));
    Http::assertSentCount(1);
    expect($target->fresh()->status)->toBe('published');
});

it('calculates shipping and GST with integer rounding', function () {
    DB::table('growya_commerce_policies')->insert(['business_id' => $this->shop->id, 'shipping_enabled' => true, 'shipping_paise' => 1000, 'free_above_paise' => 20000, 'tax_enabled' => true, 'tax_basis_points' => 300, 'seller_state' => 'Uttar Pradesh']);
    $q = app(CommerceService::class)->quote($this->shop, 10000, 1000, 'Uttar Pradesh');
    expect($q['shipping_paise'])->toBe(1000)->and($q['tax_paise'])->toBe(300)->and($q['total_paise'])->toBe(10300)->and($q['snapshot']['cgst_paise'])->toBe(150)->and($q['snapshot']['igst_paise'])->toBe(0);
});
it('blocks delivery completion through legacy order status routes without OTP', function () {
    Sanctum::actingAs($this->actor, ['growya']);
    $o = extensionOrder($this);
    DB::table('growya_commerce_policies')->insert(['business_id' => $this->shop->id, 'delivery_otp_required' => true]);
    $this->patchJson('/api/v1/businesses/'.$this->shop->id.'/orders/'.$o->id.'/status', ['status' => 'delivered'])->assertUnprocessable();
    expect($o->fresh()->status)->toBe('shipped');
});
it('persists delivery OTP attempts and completes after verification', function () {
    Sanctum::actingAs($this->actor, ['growya']);
    $o = extensionOrder($this);
    DB::table('growya_commerce_policies')->insert(['business_id' => $this->shop->id, 'delivery_otp_required' => true]);
    DB::table('growya_delivery_proofs')->insert(['order_id' => $o->id, 'otp_hash' => Hash::make('123456'), 'expires_at' => now()->addMinutes(5), 'attempts' => 0]);
    $prefix = '/api/v1/businesses/'.$this->shop->id.'/orders/'.$o->id.'/delivery';
    $this->postJson($prefix.'/verify-otp', ['code' => '000000'])->assertUnprocessable();
    expect(DB::table('growya_delivery_proofs')->value('attempts'))->toBe(1);
    $this->postJson($prefix.'/verify-otp', ['code' => '123456'])->assertOk();
    $this->postJson($prefix.'/complete')->assertOk();
    expect($o->fresh()->status)->toBe('delivered');
});

it('rejects forged Razorpay callbacks and webhooks', function () {
    config(['growya_extensions.razorpay.enabled' => true, 'growya_extensions.razorpay.key' => 'key', 'growya_extensions.razorpay.secret' => 'secret', 'growya_extensions.razorpay.webhook_secret' => 'webhook']);
    Sanctum::actingAs($this->actor, ['growya']);
    $o = extensionOrder($this);
    DB::table('growya_gateway_payments')->insert(['order_id' => $o->id, 'request_key' => 'k', 'provider_order_id' => 'order_demo', 'amount_paise' => 10000, 'currency' => 'INR', 'status' => 'created']);
    $this->postJson('/api/v1/my-orders/'.$o->id.'/gateway/verify', ['razorpay_order_id' => 'order_demo', 'razorpay_payment_id' => 'pay_demo', 'razorpay_signature' => str_repeat('0', 64)])->assertUnprocessable();
    $this->postJson('/api/v1/webhooks/razorpay', ['event' => 'payment.captured'])->assertUnauthorized();
    expect($o->fresh()->payment_status)->toBe('pending');
    Http::assertNothingSent();
});
it('verifies captured payment amount and replays safely', function () {
    config(['growya_extensions.razorpay.enabled' => true, 'growya_extensions.razorpay.key' => 'key', 'growya_extensions.razorpay.secret' => 'secret']);
    $o = extensionOrder($this);
    $id = DB::table('growya_gateway_payments')->insertGetId(['order_id' => $o->id, 'request_key' => 'k', 'provider_order_id' => 'order_demo', 'amount_paise' => 10000, 'currency' => 'INR', 'status' => 'created']);
    Http::fake(['api.razorpay.com/*' => Http::response(['id' => 'pay_demo', 'order_id' => 'order_demo', 'amount' => 10000, 'currency' => 'INR', 'status' => 'captured'], 200)]);
    $service = app(RazorpayService::class);
    $service->reconcile('pay_demo', $id);
    $service->reconcile('pay_demo', $id);
    expect($o->fresh()->payment_status)->toBe('paid');
    expect(DB::table('growya_gateway_payments')->where('id', $id)->value('status'))->toBe('paid');
});

it('requires balanced journals and prevents cross-tenant accounts', function () {
    Sanctum::actingAs($this->actor, ['growya']);
    $a = DB::table('growya_journal_accounts')->insertGetId(['business_id' => $this->shop->id, 'code' => 'CASH', 'name' => 'Cash', 'type' => 'asset']);
    $d = ['request_key' => 'j1', 'entry_date' => today()->toDateString(), 'memo' => 'Test', 'lines' => [['account_id' => $a, 'debit_paise' => 1000, 'credit_paise' => 0], ['account_id' => $a, 'debit_paise' => 0, 'credit_paise' => 900]]];
    $this->postJson('/api/v1/businesses/'.$this->shop->id.'/accounting/journals', $d)->assertUnprocessable();
    expect(DB::table('growya_journals')->count())->toBe(0);
    $d['lines'][1]['credit_paise'] = 1000;
    $d['lines'][1]['account_id'] = 99999;
    $this->postJson('/api/v1/businesses/'.$this->shop->id.'/accounting/journals', $d)->assertUnprocessable();
});
it('posts an immutable journal and reverses it only once', function () {
    Sanctum::actingAs($this->actor, ['growya']);
    $ids = [];
    foreach (['CASH', 'SALES'] as $i => $code) {
        $ids[] = DB::table('growya_journal_accounts')->insertGetId(['business_id' => $this->shop->id, 'code' => $code, 'name' => $code, 'type' => $i === 0 ? 'asset' : 'income', 'is_cash' => $i === 0]);
    }$d = ['request_key' => 'journal', 'entry_date' => today()->toDateString(), 'memo' => 'Sale', 'lines' => [['account_id' => $ids[0], 'debit_paise' => 10000, 'credit_paise' => 0], ['account_id' => $ids[1], 'debit_paise' => 0, 'credit_paise' => 10000]]];
    $url = '/api/v1/businesses/'.$this->shop->id.'/accounting';
    $r = $this->postJson($url.'/journals', $d)->assertCreated();
    $this->postJson($url.'/journals', $d)->assertCreated();
    expect(DB::table('growya_journals')->count())->toBe(1);
    $this->getJson($url.'/reports')->assertOk()->assertJsonPath('data.net_cash_movement_paise', 10000);
    $reverse = ['request_key' => 'r1', 'entry_date' => today()->toDateString(), 'memo' => 'Cancel sale'];
    $this->postJson($url.'/journals/'.$r->json('data.id').'/reverse', $reverse)->assertCreated();
    $reverse['request_key'] = 'r2';
    $this->postJson($url.'/journals/'.$r->json('data.id').'/reverse', $reverse)->assertUnprocessable();
    $this->getJson($url.'/reports')->assertOk()->assertJsonPath('data.net_cash_movement_paise', 0);
});
it('refuses storing provider secrets as public presentation settings', function () {
    Sanctum::actingAs($this->actor, ['growya']);
    $this->actor->assignRole('super_admin');
    $this->postJson('/api/v1/admin/tools/settings', ['group_name' => 'branding', 'key_name' => 'api_secret', 'value' => 'secret', 'is_public' => true])->assertUnprocessable();
});

it('does not grant AI generation from read-only staff access', function () {
    $member = BusinessUser::create(['business_id' => $this->shop->id, 'user_id' => $this->outside->id, 'role' => 'manager', 'is_active' => true]);
    $member->permissions()->sync(Permission::where('name', 'ai.view')->pluck('id'));
    Sanctum::actingAs($this->outside, ['growya']);
    $this->getJson('/api/v1/businesses/'.$this->shop->id.'/ai')->assertOk();
    $this->postJson('/api/v1/businesses/'.$this->shop->id.'/ai', [])->assertForbidden();
});
it('rejects a reused journal key with changed monetary lines', function () {
    $a = DB::table('growya_journal_accounts')->insertGetId(['business_id' => $this->shop->id, 'code' => 'C', 'name' => 'Cash', 'type' => 'asset']);
    $b = DB::table('growya_journal_accounts')->insertGetId(['business_id' => $this->shop->id, 'code' => 'S', 'name' => 'Sales', 'type' => 'income']);
    $d = ['request_key' => 'same', 'entry_date' => today()->toDateString(), 'memo' => 'Entry', 'lines' => [['account_id' => $a, 'debit_paise' => 100, 'credit_paise' => 0], ['account_id' => $b, 'debit_paise' => 0, 'credit_paise' => 100]]];
    $service = app(JournalService::class);
    $service->post($this->shop->id, $this->actor->id, $d);
    $d['lines'][0]['debit_paise'] = 200;
    $d['lines'][1]['credit_paise'] = 200;
    expect(fn () => $service->post($this->shop->id, $this->actor->id, $d))->toThrow(ValidationException::class);
    expect(DB::table('growya_journals')->count())->toBe(1);
});
it('recovers a gateway order created before a network interruption', function () {
    config(['growya_extensions.razorpay.enabled' => true, 'growya_extensions.razorpay.key' => 'key', 'growya_extensions.razorpay.secret' => 'secret']);
    $o = extensionOrder($this);
    $id = DB::table('growya_gateway_payments')->insertGetId(['order_id' => $o->id, 'request_key' => 'k', 'amount_paise' => 10000, 'currency' => 'INR', 'status' => 'unknown']);
    Http::fake(['api.razorpay.com/v1/payments/pay_demo' => Http::response(['id' => 'pay_demo', 'order_id' => 'order_demo', 'amount' => 10000, 'currency' => 'INR', 'status' => 'captured'], 200), 'api.razorpay.com/v1/orders/order_demo' => Http::response(['id' => 'order_demo', 'receipt' => 'growya-'.$id, 'amount' => 10000, 'currency' => 'INR'], 200)]);
    app(RazorpayService::class)->reconcile('pay_demo', $id);
    expect($o->fresh()->payment_status)->toBe('paid');
});
it('keeps FCM delivery acknowledgements scoped to their recipient', function () {
    $n = Notification::create(['type' => 'test', 'title' => 'Hello', 'message' => 'Message', 'target_type' => 'users', 'status' => 'sent']);
    $recipient = NotificationRecipient::create(['notification_id' => $n->id, 'user_id' => $this->actor->id, 'status' => 'sent']);
    $device = UserDevice::create(['user_id' => $this->actor->id, 'fcm_token' => str_repeat('a', 40), 'platform' => 'android', 'is_active' => true]);
    $id = DB::table('growya_push_deliveries')->insertGetId(['recipient_id' => $recipient->id, 'device_id' => $device->id, 'status' => 'accepted']);
    Sanctum::actingAs($this->outside, ['growya']);
    $this->postJson('/api/v1/push-deliveries/'.$id.'/ack', ['event' => 'opened'])->assertNotFound();
    Sanctum::actingAs($this->actor, ['growya']);
    $this->postJson('/api/v1/push-deliveries/'.$id.'/ack', ['event' => 'opened'])->assertOk();
    $this->postJson('/api/v1/push-deliveries/'.$id.'/ack', ['event' => 'delivered'])->assertOk();
    expect(DB::table('growya_push_deliveries')->value('status'))->toBe('opened');
});
it('requires delivery proof on both completion paths', function () {
    Sanctum::actingAs($this->actor, ['growya']);
    $o = extensionOrder($this);
    DB::table('growya_commerce_policies')->insert(['business_id' => $this->shop->id, 'delivery_proof_required' => true]);
    $this->postJson('/api/v1/businesses/'.$this->shop->id.'/orders/'.$o->id.'/delivery/complete')->assertUnprocessable();
    $this->patchJson('/api/v1/businesses/'.$this->shop->id.'/orders/'.$o->id.'/delivery', ['stage' => 'delivered', 'courier_name' => 'Courier'])->assertUnprocessable();
});
it('renders review forms with approved photos', function () {
    $review = Review::withoutEvents(fn () => Review::create(['business_id' => $this->shop->id, 'user_id' => $this->actor->id, 'rating' => 5, 'review' => 'Very good service', 'status' => 'approved', 'helpful_count' => 0]));
    $review->review_images()->create(['image' => 'review-photos/demo.png']);
    $this->actingAs($this->actor)->get('/b/'.$this->shop->slug.'/reviews')->assertOk()->assertSee('Your review photos');
});

it('quotes checkout charges and rejects a changed quoted total before stock writes', function () {
    Sanctum::actingAs($this->actor, ['growya']);
    $p = Product::create(['business_id' => $this->shop->id, 'name' => 'Item', 'slug' => 'item', 'price' => '100.00', 'stock_status' => 'in_stock', 'stock_quantity' => 5, 'status' => 'active']);
    $cart = Cart::create(['business_id' => $this->shop->id, 'user_id' => $this->actor->id, 'status' => 'active']);
    $cart->cart_items()->create(['product_id' => $p->id, 'quantity' => 1, 'price' => '100.00']);
    DB::table('growya_commerce_policies')->insert(['business_id' => $this->shop->id, 'shipping_enabled' => true, 'shipping_paise' => 500]);
    $url = '/api/v1/shops/'.$this->shop->id;
    $q = $this->postJson($url.'/quote', ['state' => 'Uttar Pradesh'])->assertOk()->assertJsonPath('data.total_paise', 10500);
    $d = ['customer_name' => 'Buyer', 'customer_phone' => '9876543210', 'address' => '123 Main road', 'city' => 'Kanpur', 'state' => 'Uttar Pradesh', 'pincode' => '208001', 'payment_method' => 'cod', 'quoted_total_paise' => 10000];
    $this->postJson($url.'/checkout', $d)->assertUnprocessable();
    expect($p->fresh()->stock_quantity)->toBe(5);
    expect(Order::count())->toBe(0);
    $d['quoted_total_paise'] = 10500;
    $this->postJson($url.'/checkout', $d)->assertOk();
    $o = Order::first();
    expect($o->total_amount)->toBe('105.00')->and($o->shipping_amount)->toBe('5.00');
});
it('sends via FCM v1 and disables an unregistered device without changing in-app history', function () {
    $private = openssl_pkey_new(['private_key_bits' => 2048, 'private_key_type' => OPENSSL_KEYTYPE_RSA]);
    openssl_pkey_export($private, $pem);
    $path = tempnam(sys_get_temp_dir(), 'fcm-test-');
    file_put_contents($path, json_encode(['project_id' => 'test-project', 'client_email' => 'sender@example.test', 'private_key' => $pem]));
    try {
        config(['growya_extensions.fcm.enabled' => true, 'growya_extensions.fcm.credentials' => $path]);
        Http::fake(['oauth2.googleapis.com/token' => Http::response(['access_token' => 'oauth-test'], 200), 'fcm.googleapis.com/*' => Http::response(['error' => ['status' => 'NOT_FOUND', 'details' => [['errorCode' => 'UNREGISTERED']]]], 404)]);
        $n = Notification::create(['type' => 'test', 'title' => 'Hello', 'message' => 'Message', 'target_type' => 'users', 'status' => 'sent']);
        $r = NotificationRecipient::create(['notification_id' => $n->id, 'user_id' => $this->actor->id, 'status' => 'sent']);
        $device = UserDevice::create(['user_id' => $this->actor->id, 'fcm_token' => str_repeat('a', 60), 'platform' => 'android', 'is_active' => true]);
        $id = DB::table('growya_push_deliveries')->insertGetId(['recipient_id' => $r->id, 'device_id' => $device->id, 'status' => 'queued']);
        (new SendPush($id))->handle(app(FcmService::class));
        expect($device->fresh()->is_active)->toBeFalse()->and($r->fresh()->status)->toBe('sent');
        expect(DB::table('growya_push_deliveries')->where('id', $id)->value('error_code'))->toBe('UNREGISTERED');
    } finally {
        unlink($path);
    }
});
it('creates API social posts and schedules with server-owned tenant IDs', function () {
    Sanctum::actingAs($this->actor, ['growya']);
    $a = SocialAccount::create(['business_id' => $this->shop->id, 'platform' => 'facebook', 'external_id' => '123', 'access_token' => 'secret', 'connection_status' => 'connected']);
    $url = '/api/v1/businesses/'.$this->shop->id.'/social';
    $this->postJson($url.'/posts', ['caption' => 'Hello world', 'account_ids' => [$a->id], 'publish_mode' => 'draft', 'request_key' => 'd'])->assertCreated()->assertJsonPath('data.business_id', $this->shop->id);
    $this->postJson($url.'/schedules', ['social_account_id' => $a->id, 'frequency' => 'daily', 'run_time' => '09:00', 'timezone' => 'Asia/Kolkata', 'caption' => 'A daily post'])->assertCreated();
    app(SocialScheduler::class)->tick();
    expect(SocialSchedule::first()->next_run_at)->not->toBeNull();
});
it('rechecks social author permissions when a queued job runs', function () {
    $member = BusinessUser::create(['business_id' => $this->shop->id, 'user_id' => $this->outside->id, 'role' => 'staff', 'is_active' => true]);
    $a = SocialAccount::create(['business_id' => $this->shop->id, 'platform' => 'facebook', 'external_id' => '123', 'access_token' => 'secret', 'connection_status' => 'connected']);
    $p = SocialPost::create(['business_id' => $this->shop->id, 'created_by' => $this->outside->id, 'caption' => 'Test', 'post_type' => 'text', 'publish_mode' => 'now', 'overall_status' => 'queued', 'scheduled_at' => now()->subMinute()]);
    $target = $p->social_post_platforms()->create(['social_account_id' => $a->id, 'status' => 'queued']);
    (new PublishSocial($target->id))->handle(app(MetaService::class));
    expect($target->fresh()->status)->toBe('failed');
    Http::assertNothingSent();
});

it('authenticates a bearer token on a conditionally public review photo', function () {
    Storage::fake('local');
    Storage::disk('local')->put('review-photos/private.png', 'photo-bytes');
    $review = Review::withoutEvents(fn () => Review::create(['business_id' => $this->shop->id, 'user_id' => $this->actor->id, 'rating' => 5, 'review' => 'Private review', 'status' => 'pending', 'helpful_count' => 0]));
    $image = $review->review_images()->create(['image' => 'review-photos/private.png']);
    $token = $this->actor->createToken('photo-reader', ['growya'])->plainTextToken;
    $this->get('/api/v1/review-photos/'.$image->id)->assertNotFound();
    $this->withHeader('Authorization', 'Bearer '.$token)->get('/api/v1/review-photos/'.$image->id)->assertOk()->assertStreamedContent('photo-bytes');
});

it('renders active banners while hiding expired ones', function () {
    Banner::create(['placement' => 'showroom', 'media' => 'banners/visible.jpg', 'cta_text' => 'Visit our collection', 'cta_url' => 'https://example.test/collection', 'sort_order' => 0, 'status' => 'active']);
    Banner::create(['placement' => 'showroom', 'media' => 'banners/expired.jpg', 'cta_text' => 'Expired offer', 'sort_order' => 0, 'status' => 'active', 'ends_at' => now()->subDay()]);
    $this->get('/b/'.$this->shop->slug)->assertOk()->assertSee('Visit our collection')->assertDontSee('Expired offer');
});

it('accepts a maximum-length admin credit idempotency key without a duplicate grant', function () {
    $this->actor->assignRole('super_admin');
    Sanctum::actingAs($this->actor, ['growya']);
    $payload = ['credits' => 100, 'request_key' => str_repeat('k', 100)];
    $url = '/api/v1/admin/businesses/'.$this->shop->id.'/ai/credits';
    $this->postJson($url, $payload)->assertOk()->assertJsonPath('data.available_credits', 100);
    $this->postJson($url, $payload)->assertOk()->assertJsonPath('data.available_credits', 100);
    expect(strlen(DB::table('ai_credit_transactions')->value('request_key')))->toBeLessThanOrEqual(100);
});
