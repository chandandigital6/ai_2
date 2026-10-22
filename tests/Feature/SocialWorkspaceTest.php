<?php

use App\Jobs\Extensions\PublishSocial;
use App\Models\Business;
use App\Models\SocialAccount;
use App\Models\SocialPost;
use App\Models\User;
use App\Services\Extensions\MetaService;
use Database\Seeders\BusinessPermissionSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    $this->seed(BusinessPermissionSeeder::class);
    $this->owner = User::factory()->create(['status' => 'active']);
    $this->business = Business::withoutEvents(fn () => Business::create(['owner_id' => $this->owner->id, 'name' => 'Social Demo Store', 'slug' => 'social-demo-store', 'status' => 'active', 'verification_status' => 'approved']));
    $this->workspace = '/businesses/'.$this->business->id.'/workspace/social';
    $this->social = '/businesses/'.$this->business->id.'/social';
    config(['growya_extensions.meta.version' => 'v24.0', 'growya_extensions.meta.app_id' => '123', 'growya_extensions.meta.secret' => 'private-secret', 'growya_extensions.meta.redirect_uri' => 'https://example.test/integrations/meta/callback']);
    Queue::fake();
    Http::preventStrayRequests();
});

it('explains empty channels and disables posting until an account connects', function () {
    $this->actingAs($this->owner)->get($this->workspace)->assertOk()->assertSee('No Facebook account connected')->assertSee('No Instagram account connected')->assertSee('Connect an account above to enable posting.');
});

it('returns the same browser to visible Page selection and saves both channels', function () {
    $this->actingAs($this->owner);
    $response = $this->post($this->social.'/connect')->assertRedirect();
    parse_str(parse_url($response->headers->get('Location'), PHP_URL_QUERY), $params);
    expect($params['auth_type'])->toBe('rerequest');
    $key = session('growya_social_connection');
    $this->mock(MetaService::class)->shouldReceive('pages')->once()->with('demo-code')->andReturn([['id' => '1234', 'name' => 'Demo Facebook Page', 'access_token' => 'private-page-token', 'instagram_business_account' => ['id' => '5678', 'username' => 'demo_instagram']]]);
    $this->get('/integrations/meta/callback?state='.$params['state'].'&code=demo-code')->assertRedirect($this->workspace);
    $this->get($this->workspace)->assertOk()->assertSee('Choose your accounts')->assertSee('Demo Facebook Page')->assertSee('demo_instagram')->assertDontSee('private-page-token');
    foreach (['facebook', 'instagram'] as $platform) {
        $this->post($this->social.'/select', ['connection_key' => $key, 'page_id' => '1234', 'platform' => $platform])->assertRedirect($this->workspace);
    }
    expect(SocialAccount::where('connection_status', 'connected')->count())->toBe(2);
    $response = $this->get($this->workspace)->assertOk()->assertSee('Demo Facebook Page')->assertSee('demo_instagram')->assertDontSee('private-page-token');
    // Render fixture for responsive browser QA; no credentials or live data.
    if (getenv('SOCIAL_QA_HTML')) {
        file_put_contents(getenv('SOCIAL_QA_HTML'), $response->getContent());
    }
});

it('shows a clear explanation when Meta returns no Pages', function () {
    $this->actingAs($this->owner);
    $response = $this->post($this->social.'/connect');
    parse_str(parse_url($response->headers->get('Location'), PHP_URL_QUERY), $params);
    $this->mock(MetaService::class)->shouldReceive('pages')->andReturn([]);
    $this->get('/integrations/meta/callback?state='.$params['state'].'&code=empty')->assertRedirect($this->workspace);
    $this->get($this->workspace)->assertSee('Facebook returned no available Pages.');
});

it('shows expired selection and missing configuration as actionable errors', function () {
    $this->actingAs($this->owner)->withSession(['growya_social_connection' => 'expired'])->get($this->workspace)->assertSee('Page selection has expired.');
    config(['growya_extensions.meta.version' => null]);
    $this->from($this->workspace)->post($this->social.'/connect')->assertRedirect($this->workspace)->assertSessionHasErrors('social_connection');
});

it('keeps mobile callback completion and private candidates scoped to their initiator', function () {
    Cache::put('meta-state:'.hash('sha256', 'mobile-state'), ['business_id' => $this->business->id, 'user_id' => $this->owner->id, 'connection' => 'mobile-key', 'web' => false], now()->addMinutes(10));
    $this->mock(MetaService::class)->shouldReceive('pages')->andReturn([['id' => '1234', 'name' => 'Mobile Page', 'access_token' => 'secret']]);
    $this->get('/integrations/meta/callback?state=mobile-state&code=mobile')->assertOk()->assertSee('Authorization received');
    $other = User::factory()->create(['status' => 'active']);
    $this->actingAs($other)->get($this->social.'/candidates?connection_key='.str_repeat('a', 64))->assertForbidden();
});

it('creates a real local post and publishes to both simulated providers with named status', function () {
    Storage::fake('public', ['url' => 'https://example.test/storage']);
    config(['filesystems.disks.public.url' => 'https://example.test/storage']);
    $accounts = collect(['facebook' => '1234', 'instagram' => '5678'])->map(fn ($id, $platform) => SocialAccount::create(['business_id' => $this->business->id, 'platform' => $platform, 'external_id' => $id, 'account_name' => 'Demo '.ucfirst($platform), 'access_token' => 'private-token', 'connection_status' => 'connected']));
    $jpeg = base64_decode('/9j/4AAQSkZJRgABAQEASABIAAD/2wBDAP//////////////////////////////////////////////////////////////////////////////////////2wBDAf//////////////////////////////////////////////////////////////////////////////////////wAARCAABAAEDASIAAhEBAxEB/8QAFQABAQAAAAAAAAAAAAAAAAAAAAX/xAAUEAEAAAAAAAAAAAAAAAAAAAAA/8QAFQEBAQAAAAAAAAAAAAAAAAAAAAX/xAAUEQEAAAAAAAAAAAAAAAAAAAAA/9oADAMBAAIRAxEAPwCdABmX/9k=');
    $this->actingAs($this->owner)->from($this->workspace)->post($this->social.'/posts', ['caption' => 'Local integration test only', 'account_ids' => $accounts->pluck('id')->all(), 'publish_mode' => 'now', 'request_key' => 'local-test', 'image' => UploadedFile::fake()->createWithContent('post.jpg', $jpeg)])->assertRedirect($this->workspace)->assertSessionHasNoErrors();
    $post = SocialPost::where('request_key', 'local-test')->firstOrFail();
    Http::fake(function ($request) {
        if (str_ends_with($request->url(), '/1234/photos')) {
            return Http::response(['post_id' => '1234_9000']);
        }
        if (str_ends_with($request->url(), '/5678/media')) {
            return Http::response(['id' => '7777']);
        }
        if (str_contains($request->url(), '/7777')) {
            return Http::response(['status_code' => 'FINISHED']);
        }
        if (str_ends_with($request->url(), '/5678/media_publish')) {
            return Http::response(['id' => '8888']);
        }

        return Http::response([], 400);
    });
    foreach ($post->social_post_platforms as $target) {
        $job = new PublishSocial($target->id);
        $job->handle(app(MetaService::class));
        if ($target->fresh()->status === 'waiting') {
            $job->handle(app(MetaService::class));
        }
        $job->handle(app(MetaService::class)); // Completed publications cannot send twice.
    }
    expect($post->fresh()->overall_status)->toBe('published');
    Http::assertSentCount(4);
    $this->get($this->workspace)->assertOk()->assertSee('Demo Facebook')->assertSee('Demo Instagram')->assertSee('View published post')->assertDontSee('private-token');
});
