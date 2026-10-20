<?php

use App\Jobs\Extensions\GenerateAi;
use App\Models\AiBrandAsset;
use App\Models\AiBrandProfile;
use App\Models\AiGeneration;
use App\Models\AiPlan;
use App\Models\AiPlanRequest;
use App\Models\AiPromptPreset;
use App\Models\Business;
use App\Models\BusinessUser;
use App\Models\User;
use App\Services\Extensions\AiFailureService;
use App\Services\Extensions\AiPlanService;
use App\Services\Extensions\AiProvider;
use App\Services\Extensions\AiWalletService;
use Database\Seeders\BusinessPermissionSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    $this->seed(BusinessPermissionSeeder::class);
    Role::findOrCreate('super_admin', 'web');
    $this->owner = User::factory()->create(['status' => 'active']);
    $this->admin = User::factory()->create(['status' => 'active']);
    $this->admin->assignRole('super_admin');
    $this->other = User::factory()->create(['status' => 'active']);
    $this->business = Business::withoutEvents(fn () => Business::create(['owner_id' => $this->owner->id, 'name' => 'AI Jewellers', 'slug' => 'ai-jewellers', 'status' => 'active', 'verification_status' => 'approved']));
    $this->plan = AiPlan::create(aiPlanPayload());
    $this->base = '/api/v1/businesses/'.$this->business->id.'/ai';
    config(['growya_extensions.ai.enabled' => true, 'growya_extensions.ai.key' => 'test-only']);
    Queue::fake();
    Storage::fake('local');
    Http::preventStrayRequests();
    Sanctum::actingAs($this->owner, ['growya']);
});

function aiPlanPayload(array $changes = []): array
{
    return array_merge(['name' => 'Test plan', 'description' => 'Test package', 'price_paise' => 99900,
        'credits' => 100, 'valid_days' => 30, 'text_model' => 'gpt-4.1-mini', 'image_model' => 'gpt-image-1',
        'text_credits' => 1, 'image_credits' => 10, 'max_prompts' => 2, 'max_references' => 2, 'is_active' => true], $changes);
}
function approveAiPack($test): AiPlanRequest
{
    Sanctum::actingAs($test->owner, ['growya']);
    $id = $test->postJson($test->base.'/plan-requests', ['ai_plan_id' => $test->plan->id, 'request_key' => 'pack-'.Str::uuid()])->assertCreated()->json('data.id');
    Sanctum::actingAs($test->admin, ['growya']);
    $test->postJson('/api/v1/admin/ai-plan-requests/'.$id.'/review', ['decision' => 'approved'])->assertOk();
    Sanctum::actingAs($test->owner, ['growya']);

    return AiPlanRequest::findOrFail($id);
}
function aiGenerationPayload(array $changes = []): array
{
    return array_merge(['generation_type' => 'text', 'prompt' => 'Write a jewellery launch caption', 'request_key' => (string) Str::uuid()], $changes);
}
function aiPng(): string
{
    return base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+a9xkAAAAASUVORK5CYII=');
}
function runAiJob(int $id): void
{
    (new GenerateAi($id))->handle(app(AiProvider::class), app(AiWalletService::class));
}

it('shows packages and renders both complete studio screens', function () {
    $pack = approveAiPack($this);
    $this->getJson($this->base.'/plans')->assertOk()->assertJsonPath('data.entitlements.available_credits', 100);
    $this->actingAs($this->owner)->get('/businesses/'.$this->business->id.'/workspace/ai')->assertOk()->assertSee('Saved prompts')->assertSee('Test plan');
    $this->actingAs($this->admin)->get('/admin/ai-plans')->assertOk()->assertSee('Owner requests')->assertSee('Test plan');
    $this->get('/admin/ai-plans?edit='.$this->plan->id)->assertOk()->assertSee('Edit package');
});
it('allows only super admin to create edit and approve packages', function () {
    $this->postJson('/api/v1/admin/ai-plans', aiPlanPayload())->assertForbidden();
    $this->putJson('/api/v1/admin/ai-plans/'.$this->plan->id, aiPlanPayload())->assertForbidden();
    Sanctum::actingAs($this->admin, ['growya']);
    $this->postJson('/api/v1/admin/ai-plans', aiPlanPayload(['name' => 'New']))->assertCreated();
    $this->putJson('/api/v1/admin/ai-plans/'.$this->plan->id, aiPlanPayload(['text_model' => 'hacked-model']))->assertUnprocessable();
});
it('blocks staff requests and all foreign business AI reads', function () {
    BusinessUser::create(['business_id' => $this->business->id, 'user_id' => $this->other->id, 'role' => 'staff', 'is_active' => true]);
    Sanctum::actingAs($this->other, ['growya']);
    $this->postJson($this->base.'/plan-requests', ['ai_plan_id' => $this->plan->id, 'request_key' => 'bad'])->assertForbidden();
    $this->getJson($this->base.'/brand')->assertForbidden();
    $this->getJson($this->base.'/plans')->assertForbidden();
});
it('deduplicates package requests and grants approval credits exactly once', function () {
    $body = ['ai_plan_id' => $this->plan->id, 'request_key' => 'same'];
    $id = $this->postJson($this->base.'/plan-requests', $body)->assertCreated()->json('data.id');
    $this->postJson($this->base.'/plan-requests', $body)->assertCreated()->assertJsonPath('data.id', $id);
    $this->postJson($this->base.'/plan-requests', $body + ['note' => 'changed'])->assertUnprocessable();
    $this->postJson($this->base.'/plan-requests', ['ai_plan_id' => $this->plan->id, 'request_key' => 'another'])->assertUnprocessable();
    $this->postJson('/api/v1/admin/ai-plan-requests/'.$id.'/review', ['decision' => 'approved'])->assertForbidden();
    Sanctum::actingAs($this->admin, ['growya']);
    $url = '/api/v1/admin/ai-plan-requests/'.$id.'/review';
    $this->postJson($url, ['decision' => 'approved'])->assertOk();
    $this->postJson($url, ['decision' => 'approved'])->assertOk();
    expect(app(AiWalletService::class)->balance($this->business->id)['available_credits'])->toBe(100);
    expect(AiPlanRequest::find($id)->expires_at->greaterThan(now()->addDays(29)))->toBeTrue();
    $this->postJson($url, ['decision' => 'rejected', 'review_note' => 'later'])->assertUnprocessable();
});
it('rejects requests with reason without granting credits', function () {
    $id = $this->postJson($this->base.'/plan-requests', ['ai_plan_id' => $this->plan->id, 'request_key' => 'reject'])->assertCreated()->json('data.id');
    Sanctum::actingAs($this->admin, ['growya']);
    $url = '/api/v1/admin/ai-plan-requests/'.$id.'/review';
    $this->postJson($url, ['decision' => 'rejected'])->assertUnprocessable();
    $this->postJson($url, ['decision' => 'rejected', 'review_note' => 'Payment not verified'])->assertOk();
    expect(app(AiWalletService::class)->balance($this->business->id)['available_credits'])->toBe(0);
});
it('requires an approved package even if legacy credits exist', function () {
    app(AiWalletService::class)->change($this->business->id, 999, 'grant', 'legacy');
    $this->postJson($this->base, aiGenerationPayload())->assertUnprocessable();
    expect(AiGeneration::count())->toBe(0);
    Http::assertNothingSent();
});
it('keeps purchased model and prices immutable and forbids client model overrides', function () {
    $pack = approveAiPack($this);
    $this->plan->update(['text_model' => 'gpt-4.1', 'text_credits' => 20]);
    $this->postJson($this->base, aiGenerationPayload(['model' => 'gpt-4.1']))->assertUnprocessable();
    $id = $this->postJson($this->base, aiGenerationPayload(['plan_request_id' => $pack->id]))->assertStatus(202)->assertJsonPath('data.model', 'gpt-4.1-mini')->json('data.id');
    expect(AiGeneration::find($id)->credits_used)->toBe(1);
    expect($pack->fresh()->used_credits)->toBe(1);
});
it('prevents expired and foreign packages and unsupported generation types', function () {
    $pack = approveAiPack($this);
    $pack->update(['expires_at' => now()->subSecond()]);
    $this->postJson($this->base, aiGenerationPayload(['plan_request_id' => $pack->id]))->assertUnprocessable();
    expect(app(AiPlanService::class)->entitlements($this->business->id)['available_credits'])->toBe(0);
    $snapshot = $pack->plan_snapshot;
    $snapshot['image_model'] = null;
    $pack->update(['expires_at' => now()->addDay(), 'plan_snapshot' => $snapshot]);
    $this->postJson($this->base, aiGenerationPayload(['generation_type' => 'image', 'plan_request_id' => $pack->id]))->assertUnprocessable();
    $pack->update(['business_id' => Business::withoutEvents(fn () => Business::create(['owner_id' => $this->other->id, 'name' => 'Other', 'slug' => 'other-ai', 'status' => 'active', 'verification_status' => 'approved']))->id]);
    $this->postJson($this->base, aiGenerationPayload(['plan_request_id' => $pack->id]))->assertUnprocessable();
});
it('deduplicates generation charges and rejects changed payload with the same key', function () {
    $pack = approveAiPack($this);
    $d = aiGenerationPayload();
    $id = $this->postJson($this->base, $d)->assertStatus(202)->json('data.id');
    $this->postJson($this->base, $d)->assertStatus(202)->assertJsonPath('data.id', $id);
    $d['prompt'] = 'Changed request';
    $this->postJson($this->base, $d)->assertUnprocessable();
    expect(AiGeneration::count())->toBe(1)->and($pack->fresh()->used_credits)->toBe(1);
    Queue::assertPushed(GenerateAi::class, 1);
});
it('protects package balance and limits concurrent generations', function () {
    $pack = approveAiPack($this);
    $pack->update(['used_credits' => 99]);
    $this->postJson($this->base, aiGenerationPayload(['generation_type' => 'image']))->assertUnprocessable();
    $pack->update(['used_credits' => 0]);
    $this->postJson($this->base, aiGenerationPayload())->assertStatus(202);
    $this->postJson($this->base, aiGenerationPayload())->assertStatus(202);
    $this->postJson($this->base, aiGenerationPayload())->assertUnprocessable();
});
it('sends logo references brand details and preset to the purchased image model', function () {
    $pack = approveAiPack($this);
    AiBrandProfile::create(['business_id' => $this->business->id, 'brand_name' => 'My gold brand', 'colors' => 'Maroon', 'language' => 'Hindi', 'default_size' => '1024x1536']);
    Storage::disk('local')->put('logo.png', aiPng());
    $logo = AiBrandAsset::create(['business_id' => $this->business->id, 'kind' => 'logo', 'label' => 'Brand logo', 'path' => 'logo.png', 'mime' => 'image/png']);
    $ref = AiBrandAsset::create(['business_id' => $this->business->id, 'kind' => 'reference', 'label' => 'Product', 'path' => 'logo.png', 'mime' => 'image/png']);
    $preset = AiPromptPreset::create(['business_id' => $this->business->id, 'name' => 'Daily poster', 'generation_type' => 'image', 'prompt' => 'Pure gold product on the right', 'reference_ids' => [$ref->id], 'size' => '1024x1536', 'language' => 'Hindi']);
    $id = $this->postJson($this->base, ['generation_type' => 'image', 'preset_id' => $preset->id, 'plan_request_id' => $pack->id, 'request_key' => 'references'])->assertStatus(202)->json('data.id');
    Http::fake(['api.openai.com/*' => Http::response(['data' => [['b64_json' => base64_encode(aiPng())]]])]);
    runAiJob($id);
    Http::assertSent(fn ($r) => $r->url() === 'https://api.openai.com/v1/images/edits' && $r['model'] === 'gpt-image-1' && count($r['images']) === 2 && str_contains($r['prompt'], 'My gold brand') && str_contains($r['prompt'], 'Pure gold product') && $r['size'] === '1024x1536');
    $result = $this->getJson($this->base.'/generations/'.$id)->assertOk()->assertJsonPath('data.status', 'completed');
    expect($result->getContent())->not->toContain('logo.png');
    $mediaId = $result->json('data.media.0.id');
    $this->get($this->base.'/media/'.$mediaId.'?inline=1')->assertOk();
    Sanctum::actingAs($this->other, ['growya']);
    $this->get($this->base.'/media/'.$mediaId.'?inline=1')->assertForbidden();
});
it('returns captions to the status API and sends visual references to text models', function () {
    approveAiPack($this);
    Storage::disk('local')->put('ref.png', aiPng());
    AiBrandAsset::create(['business_id' => $this->business->id, 'kind' => 'logo', 'label' => 'Logo', 'path' => 'ref.png', 'mime' => 'image/png']);
    $id = $this->postJson($this->base, aiGenerationPayload())->assertStatus(202)->json('data.id');
    Http::fake(['api.openai.com/*' => Http::response(['output' => [['content' => [['type' => 'output_text', 'text' => 'Your new collection is here!']]]]])]);
    runAiJob($id);
    $this->getJson($this->base.'/generations/'.$id)->assertOk()->assertJsonPath('data.output_json.text', 'Your new collection is here!');
    Http::assertSent(fn ($r) => $r['model'] === 'gpt-4.1-mini' && $r['input'][0]['content'][1]['type'] === 'input_image');
    runAiJob($id);
    Http::assertSentCount(1);
});
it('maps provider errors and refunds both wallet and the original package exactly once', function (int $status, string $code, string $expected) {
    $pack = approveAiPack($this);
    $id = $this->postJson($this->base, aiGenerationPayload())->assertStatus(202)->json('data.id');
    Http::fake(['api.openai.com/*' => Http::response(['error' => ['code' => $code, 'message' => 'secret raw provider error']], $status)]);
    runAiJob($id);
    (new GenerateAi($id))->failed(null);
    $this->getJson($this->base.'/generations/'.$id)->assertJsonPath('data.status', 'failed')->assertJsonPath('data.error_code', $expected)->assertDontSee('secret raw provider error');
    expect($pack->fresh()->used_credits)->toBe(0)->and(app(AiWalletService::class)->balance($this->business->id)['available_credits'])->toBe(100);
})->with([[429, 'rate_limit_exceeded', 'provider_rate_limit'], [429, 'insufficient_quota', 'provider_quota'], [401, 'invalid_api_key', 'provider_auth'], [403, 'model_not_found', 'model_unavailable'], [500, '', 'provider_unavailable']]);
it('recovers stale jobs and will never generate after a refund', function () {
    approveAiPack($this);
    $id = $this->postJson($this->base, aiGenerationPayload())->assertStatus(202)->json('data.id');
    AiGeneration::whereKey($id)->update(['created_at' => now()->subMinutes(31)]);
    $this->artisan('growya:ai-recover')->assertSuccessful();
    runAiJob($id);
    expect(AiGeneration::find($id)->status)->toBe('failed');
    Http::assertNothingSent();
});
it('allows brand logo before a plan and enforces active reference and prompt quotas', function () {
    $upload = fn () => UploadedFile::fake()->createWithContent('brand.png', aiPng());
    $this->postJson($this->base.'/assets', ['kind' => 'logo', 'label' => 'Logo', 'image' => $upload()])->assertCreated();
    $this->postJson($this->base.'/assets', ['kind' => 'reference', 'label' => 'Product', 'image' => $upload()])->assertUnprocessable();
    $this->plan->update(['max_prompts' => 1, 'max_references' => 1]);
    approveAiPack($this);
    $this->postJson($this->base.'/assets', ['kind' => 'reference', 'label' => 'Product', 'image' => $upload()])->assertCreated();
    $this->postJson($this->base.'/assets', ['kind' => 'reference', 'label' => 'More', 'image' => $upload()])->assertUnprocessable();
    $body = ['name' => 'Daily', 'generation_type' => 'image', 'prompt' => 'Jewellery product poster', 'language' => 'Hindi', 'size' => '1024x1024'];
    $id = $this->postJson($this->base.'/presets', $body)->assertCreated()->json('data.id');
    $this->postJson($this->base.'/presets', $body)->assertUnprocessable();
    $this->putJson($this->base.'/presets/'.$id, $body + ['negative_prompt' => 'No blur'])->assertOk();
    $this->deleteJson($this->base.'/presets/'.$id)->assertOk();
    $this->postJson($this->base.'/presets', $body)->assertCreated();
});
it('protects private assets and preset modifications from other businesses', function () {
    approveAiPack($this);
    Storage::disk('local')->put('private.png', aiPng());
    $asset = AiBrandAsset::create(['business_id' => $this->business->id, 'kind' => 'reference', 'label' => 'Private', 'path' => 'private.png', 'mime' => 'image/png']);
    Sanctum::actingAs($this->other, ['growya']);
    $this->get($this->base.'/assets/'.$asset->id)->assertForbidden();
    $this->deleteJson($this->base.'/assets/'.$asset->id)->assertForbidden();
    Sanctum::actingAs($this->owner, ['growya']);
    $this->getJson($this->base.'/brand')->assertDontSee('private.png');
});
it('returns a JSON rate limit message with retry time', function () {
    config(['growya_ai.generation_rate' => 1]);
    approveAiPack($this);
    $this->postJson($this->base, aiGenerationPayload())->assertStatus(202);
    $this->postJson($this->base, aiGenerationPayload())->assertStatus(429)->assertJsonPath('error_code', 'app_rate_limit')->assertJsonStructure(['message', 'retry_after']);
});

it('accepts rupee prices exactly and keeps plan snapshot after edits', function () {
    Sanctum::actingAs($this->admin, ['growya']);
    $d = aiPlanPayload(['name' => 'Rupee plan']);
    unset($d['price_paise']);
    $d['price_rupees'] = '999.05';
    $this->postJson('/api/v1/admin/ai-plans', $d)->assertCreated()->assertJsonPath('data.price_paise', 99905);
    $d['price_rupees'] = '1.234';
    $this->postJson('/api/v1/admin/ai-plans', $d)->assertUnprocessable();
});
it('rejects inactive plans and prevents foreign reference use and preset edits', function () {
    $this->plan->update(['is_active' => false]);
    $this->postJson($this->base.'/plan-requests', ['ai_plan_id' => $this->plan->id, 'request_key' => 'inactive'])->assertNotFound();
    $this->plan->update(['is_active' => true]);
    approveAiPack($this);
    $foreign = Business::withoutEvents(fn () => Business::create(['owner_id' => $this->other->id, 'name' => 'Other', 'slug' => 'other-reference', 'status' => 'active', 'verification_status' => 'approved']));
    $asset = AiBrandAsset::create(['business_id' => $foreign->id, 'kind' => 'reference', 'label' => 'Other', 'path' => 'other.png', 'mime' => 'image/png']);
    $preset = AiPromptPreset::create(['business_id' => $foreign->id, 'name' => 'Private', 'generation_type' => 'text', 'prompt' => 'Private prompt', 'language' => 'Hindi', 'size' => '1024x1024']);
    $this->postJson($this->base, aiGenerationPayload(['reference_ids' => [$asset->id]]))->assertUnprocessable();
    $this->postJson($this->base, aiGenerationPayload(['preset_id' => $preset->id]))->assertNotFound();
    $this->deleteJson($this->base.'/presets/'.$preset->id)->assertNotFound();
    expect(AiGeneration::count())->toBe(0);
});
it('keeps queued reference bytes immutable even if the owner removes the asset', function () {
    approveAiPack($this);
    Storage::disk('local')->put('immutable.png', aiPng());
    $asset = AiBrandAsset::create(['business_id' => $this->business->id, 'kind' => 'reference', 'label' => 'Original', 'path' => 'immutable.png', 'mime' => 'image/png']);
    $id = $this->postJson($this->base, aiGenerationPayload(['generation_type' => 'image']))->assertStatus(202)->json('data.id');
    $this->deleteJson($this->base.'/assets/'.$asset->id)->assertOk();
    Http::fake(['api.openai.com/*' => Http::response(['data' => [['b64_json' => base64_encode(aiPng())]]])]);
    runAiJob($id);
    expect(AiGeneration::find($id)->status)->toBe('completed');
    $this->get($this->base.'/assets/'.$asset->id)->assertNotFound();
});
it('does not spend provider credits when a queued staff permission was revoked', function () {
    $pack = approveAiPack($this);
    $member = BusinessUser::create(['business_id' => $this->business->id, 'user_id' => $this->other->id, 'role' => 'staff', 'is_active' => true]);
    $id = $this->postJson($this->base, aiGenerationPayload())->assertStatus(202)->json('data.id');
    AiGeneration::find($id)->update(['user_id' => $this->other->id]);
    runAiJob($id);
    expect(AiGeneration::find($id)->error_code)->toBe('access_revoked')->and($pack->fresh()->used_credits)->toBe(0);
    Http::assertNothingSent();
});
it('uses the plain image generation endpoint without references', function () {
    approveAiPack($this);
    $id = $this->postJson($this->base, aiGenerationPayload(['generation_type' => 'image']))->assertStatus(202)->json('data.id');
    Http::fake(['api.openai.com/*' => Http::response(['data' => [['b64_json' => base64_encode(aiPng())]]])]);
    runAiJob($id);
    Http::assertSent(fn ($r) => $r->url() === 'https://api.openai.com/v1/images/generations' && $r['model'] === 'gpt-image-1');
    expect(AiGeneration::find($id)->status)->toBe('completed');
});
it('does not refund a newly started processing job as stale', function () {
    approveAiPack($this);
    $id = $this->postJson($this->base, aiGenerationPayload())->assertStatus(202)->json('data.id');
    AiGeneration::whereKey($id)->update(['created_at' => now()->subHour(), 'status' => 'processing']);
    app(AiFailureService::class)->fail($id, null, true);
    expect(AiGeneration::find($id)->status)->toBe('processing');
});
it('enforces preset type selected package reference quota and a fresh variation per generation', function () {
    approveAiPack($this);
    $preset = AiPromptPreset::create(['business_id' => $this->business->id, 'name' => 'Image only', 'generation_type' => 'image', 'prompt' => 'Premium gold product poster', 'size' => '1024x1024', 'language' => 'Hindi']);
    $this->postJson($this->base, aiGenerationPayload(['preset_id' => $preset->id]))->assertUnprocessable();
    $id = $this->postJson($this->base, aiGenerationPayload())->assertStatus(202)->json('data.id');
    $second = $this->postJson($this->base, aiGenerationPayload())->assertStatus(202)->json('data.id');
    expect(AiGeneration::find($id)->input_json['variation_key'])->not->toBe(AiGeneration::find($second)->input_json['variation_key']);
});
