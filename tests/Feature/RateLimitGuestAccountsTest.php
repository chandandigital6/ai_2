<?php

use App\Models\Business;
use App\Models\GrowyaChatThread;
use App\Models\Product;
use App\Models\Service;
use App\Models\User;
use Database\Seeders\BusinessPermissionSeeder;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    Queue::fake();
    Http::preventStrayRequests();
    $this->seed(BusinessPermissionSeeder::class);
    $this->owner = User::factory()->create(['user_type' => 'owner']);
    $this->buyer = User::factory()->create();
    $this->shop = Business::withoutEvents(fn () => Business::create(['owner_id' => $this->owner->id, 'name' => 'Public Test Shop', 'slug' => 'public-test-shop', 'status' => 'active', 'verification_status' => 'approved']));
    $this->product = Product::create(['business_id' => $this->shop->id, 'name' => 'Public Product', 'slug' => 'public-product', 'price' => 100, 'stock_quantity' => 20, 'stock_status' => 'in_stock', 'status' => 'active']);
    $this->service = Service::create(['business_id' => $this->shop->id, 'name' => 'Public Service', 'slug' => 'public-service', 'status' => 'active']);
});

it('isolates numeric rate limits across routes while enforcing each budget and retry headers', function () {
    Route::get('/rate-test/read', fn () => ['ok' => true])->middleware('throttle:8,1')->name('rate-test.read');
    Route::post('/rate-test/write/{id}', fn () => ['ok' => true])->middleware('throttle:2,1')->name('rate-test.write');
    for ($i = 0; $i < 8; $i++) {
        $this->getJson('/rate-test/read')->assertOk();
    }
    $this->postJson('/rate-test/write/1')->assertOk();
    $this->postJson('/rate-test/write/2')->assertOk();
    $this->postJson('/rate-test/write/3')->assertStatus(429)->assertHeader('Retry-After')->assertJsonPath('error_code', 'app_rate_limit');
    $this->getJson('/rate-test/read')->assertStatus(429);
    $this->travel(61)->seconds();
    $this->postJson('/rate-test/write/4')->assertOk();
});

it('does not double count layered numeric limits or bypass named limits', function () {
    Route::get('/rate-test/layered', fn () => ['ok' => true])->middleware(['throttle:20,1', 'throttle:3,1'])->name('rate-test.layered');
    for ($i = 0; $i < 3; $i++) {
        $this->getJson('/rate-test/layered')->assertOk();
    }
    $this->getJson('/rate-test/layered')->assertStatus(429);
    RateLimiter::for('test-named', fn () => Limit::perMinute(1)->by('shared-actor'));
    Route::get('/rate-test/named-a', fn () => ['ok' => true])->middleware('throttle:test-named')->name('rate-test.named-a');
    Route::get('/rate-test/named-b', fn () => ['ok' => true])->middleware('throttle:test-named')->name('rate-test.named-b');
    $this->getJson('/rate-test/named-a')->assertOk();
    $this->getJson('/rate-test/named-b')->assertStatus(429);
});

it('keeps chat send and browser config usable after repeated chat and notification polling', function () {
    $thread = GrowyaChatThread::create(['business_id' => $this->shop->id, 'customer_id' => $this->buyer->id, 'context_type' => 'general', 'context_id' => 0]);
    $this->actingAs($this->buyer);
    for ($i = 0; $i < 25; $i++) {
        $this->getJson('/messages/'.$thread->id.'/poll')->assertOk();
        $this->getJson('/my-notifications/poll')->assertOk();
    }
    $this->post('/messages/'.$thread->id, ['body' => 'Hello owner'])->assertRedirect();
    $this->getJson('/my-notifications/push/config')->assertOk();
    $this->assertDatabaseHas('growya_chat_messages', ['thread_id' => $thread->id, 'body' => 'Hello owner']);
});

it('keeps API polling and sending independent for a bearer token', function () {
    $thread = GrowyaChatThread::create(['business_id' => $this->shop->id, 'customer_id' => $this->buyer->id, 'context_type' => 'general', 'context_id' => 0]);
    $token = $this->buyer->createToken('test', ['growya'])->plainTextToken;
    $this->withToken($token);
    for ($i = 0; $i < 25; $i++) {
        $this->getJson('/api/v1/messages/'.$thread->id.'/poll')->assertOk();
        $this->getJson('/api/v1/my-notifications/poll')->assertOk();
    }
    $this->postJson('/api/v1/messages/'.$thread->id, ['body' => 'API hello'])->assertSuccessful();
});

it('lets guests browse every public catalog page without logging in', function (string $page) {
    $url = strtr($page, ['{slug}' => $this->shop->slug, '{product}' => $this->product->slug, '{service}' => $this->service->slug]);
    $this->get($url)->assertOk();
    $this->getJson('/api/v1'.$url)->assertOk();
})->with(['/marketplace', '/search?type=products', '/search?type=services', '/b/{slug}', '/b/{slug}/products', '/b/{slug}/products/{product}', '/b/{slug}/services', '/b/{slug}/services/{service}']);

it('hides inactive products services and unapproved businesses from guests', function () {
    $this->product->update(['status' => 'inactive']);
    $this->service->update(['status' => 'inactive']);
    foreach (['', '/api/v1'] as $prefix) {
        $this->getJson($prefix.'/b/'.$this->shop->slug.'/products/'.$this->product->slug)->assertNotFound();
        $this->getJson($prefix.'/b/'.$this->shop->slug.'/services/'.$this->service->slug)->assertNotFound();
    }
    $this->shop->update(['verification_status' => 'pending']);
    $this->get('/b/'.$this->shop->slug)->assertNotFound();
});

it('requires login for enquiry forms submissions and checkout on web and API', function () {
    $urls = ['/products/'.$this->shop->id.'/'.$this->product->id.'/enquiry', '/s/'.$this->shop->id.'/'.$this->service->id.'/enquiry', '/shops/'.$this->shop->id.'/checkout'];
    foreach ($urls as $url) {
        $this->get($url)->assertRedirect(route('login'));
        $this->post($url, [])->assertRedirect(route('login'));
        $this->getJson('/api/v1'.$url)->assertUnauthorized();
        $this->postJson('/api/v1'.$url, [])->assertUnauthorized();
    }
    $this->assertDatabaseCount('service_enquiries', 0);
    $this->assertDatabaseCount('business_enquiries', 0);
    $this->assertDatabaseCount('orders', 0);
});

it('returns a newly registered shopper to the intended enquiry for both account purposes', function (string $type) {
    $url = '/products/'.$this->shop->id.'/'.$this->product->id.'/enquiry';
    $this->get($url)->assertRedirect(route('login'));
    $this->post('/register', ['name' => 'New shopper', 'email' => 'new@example.test', 'phone' => '9876543210', 'password' => 'Password123!', 'password_confirmation' => 'Password123!', 'account_type' => $type])->assertSessionHasNoErrors()->assertRedirect($url);
    $user = User::where('email', 'new@example.test')->firstOrFail();
    expect($user->account_type)->toBe($type);
    $this->get($url)->assertOk();
})->with(['personal', 'business']);

it('allows authenticated enquiry submission and attributes it to the customer', function () {
    $this->actingAs($this->buyer)->post('/products/'.$this->shop->id.'/'.$this->product->id.'/enquiry', ['name' => 'Buyer', 'phone' => '9876543210', 'quantity' => 1])->assertSessionHasNoErrors()->assertRedirect();
    $token = $this->buyer->createToken('test', ['growya'])->plainTextToken;
    $this->withToken($token)->postJson('/api/v1/s/'.$this->shop->id.'/'.$this->service->id.'/enquiry', ['customer_name' => 'Buyer', 'phone' => '9876543210'])->assertCreated();
    $this->assertDatabaseHas('business_enquiries', ['user_id' => $this->buyer->id]);
    $this->assertDatabaseHas('service_enquiries', ['user_id' => $this->buyer->id]);
});

it('shows and filters account purposes separately from roles for the super admin', function () {
    $admin = User::factory()->create();
    $admin->assignRole(Role::findOrCreate('super_admin', 'web'));
    $this->actingAs($admin);
    Livewire::test('users.user-crud')->assertSee('Account type / Roles')->assertSee('Business')->assertSee('Personal')
        ->set('accountType', 'business')->assertSee($this->owner->email)->assertDontSee($this->buyer->email)
        ->set('accountType', 'personal')->assertSee($this->buyer->email)->assertDontSee($this->owner->email);
    $token = $admin->createToken('test', ['growya'])->plainTextToken;
    $this->withToken($token)->getJson('/api/v1/users?account_type=business')->assertOk()->assertJsonCount(1, 'data.data')->assertJsonPath('data.data.0.account_type', 'business');
    $this->actingAs($this->buyer)->get('/users')->assertForbidden();
});
