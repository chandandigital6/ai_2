<?php

namespace App\Http\Controllers\Extensions;

use App\Models\Business;
use App\Models\SocialAccount;
use App\Models\SocialPost;
use App\Models\SocialSchedule;
use App\Models\User;
use App\Services\BusinessAccessService;
use App\Services\Extensions\MetaService;
use App\Services\Extensions\SocialScheduler;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;

class SocialController extends ExtensionController
{
    public function accounts(Request $r, Business $business)
    {
        $this->business($r, $business, 'social.view');

        return response()->json(['data' => SocialAccount::where('business_id', $business->id)->get()]);
    }

    public function connect(Request $r, Business $business, MetaService $meta)
    {
        $this->business($r, $business, 'social.manage');
        try {
            $meta->base();
        } catch (HttpException $e) {
            if ($e->getStatusCode() !== 503) {
                throw $e;
            }
            throw ValidationException::withMessages(['social_connection' => 'Facebook connection is not configured. Ask the administrator to set the Meta App ID, secret, API version and HTTPS callback URL.']);
        }
        $state = Str::random(64);
        $connection = Str::random(64);
        Cache::put('meta-state:'.hash('sha256', $state), ['business_id' => $business->id, 'user_id' => $r->user()->id, 'connection' => $connection, 'web' => ! $r->is('api/*')], now()->addMinutes(10));
        $url = 'https://www.facebook.com/'.config('growya_extensions.meta.version').'/dialog/oauth?'.http_build_query(['client_id' => config('growya_extensions.meta.app_id'), 'redirect_uri' => config('growya_extensions.meta.redirect_uri'), 'state' => $state, 'auth_type' => 'rerequest', 'scope' => 'pages_show_list,pages_read_engagement,pages_manage_posts,instagram_basic,instagram_content_publish']);
        if (! $r->is('api/*')) {
            $r->session()->put('growya_social_connection', $connection);

            return redirect()->away($url);
        }

        return response()->json(['data' => ['authorization_url' => $url, 'connection_key' => $connection, 'expires_in' => 600]]);
    }

    public function callback(Request $r, MetaService $meta)
    {
        $d = $r->validate(['state' => ['required', 'string', 'max:100'], 'code' => ['nullable', 'string', 'max:4000'], 'error' => ['nullable', 'string', 'max:200']]);
        $context = Cache::pull('meta-state:'.hash('sha256', $d['state']));
        abort_unless($context, 422, 'OAuth state expired or already used.');
        $user = User::findOrFail($context['user_id']);
        $b = Business::findOrFail($context['business_id']);
        abort_unless(app(BusinessAccessService::class)->can($user, $b, 'social.manage'), 403);
        $pages = [];
        $error = null;
        if (! empty($d['code'])) {
            try {
                $pages = $meta->pages($d['code']);
                if (empty($pages)) {
                    $error = 'Facebook returned no available Pages. Reconnect, allow Page access and select a Page you manage. Instagram must be a professional account linked to that Page.';
                }
            } catch (\Throwable $e) {
                $error = 'Meta connection failed. Check app access, permissions and callback configuration.';
            }
        } else {
            $error = 'Connection cancelled.';
        }
        Cache::put('meta-result:'.hash('sha256', $context['connection']), encrypt(['business_id' => $b->id, 'user_id' => $user->id, 'pages' => $pages, 'error' => $error]), now()->addMinutes(10));

        // Only redirect the same signed-in browser that started this connection.
        // API/mobile callbacks keep their existing completion page contract.
        if (($context['web'] ?? false) && $r->user()?->id === $user->id
            && $r->session()->get('growya_social_connection') === $context['connection']) {
            return redirect()->route('extensions.workspace', [$b, 'social'])
                ->with('social_notice', $error ?: 'Authorization received. Choose the Facebook Page or Instagram account below to finish connecting.');
        }

        return response()->view('extensions.oauth-complete', ['business' => $b, 'error' => $error]);
    }

    private function result(Request $r, Business $b, string $key): array
    {
        $value = Cache::get('meta-result:'.hash('sha256', $key));
        abort_unless($value, 404, 'Connection still pending or expired.');
        $data = decrypt($value);
        abort_unless((int) $data['user_id'] === (int) $r->user()->id && (int) $data['business_id'] === (int) $b->id, 404);

        return $data;
    }

    public function candidates(Request $r, Business $business)
    {
        $this->business($r, $business, 'social.manage');
        $d = $r->validate(['connection_key' => ['required', 'string', 'size:64']]);
        $data = $this->result($r, $business, $d['connection_key']);

        return response()->json(['data' => ['error' => $data['error'], 'pages' => array_map(fn ($p) => ['id' => $p['id'], 'name' => $p['name'] ?? 'Facebook Page', 'instagram' => $p['instagram_business_account'] ?? null], $data['pages'])]]);
    }

    public function select(Request $r, Business $business)
    {
        $this->business($r, $business, 'social.manage');
        $d = $r->validate(['connection_key' => ['required', 'string', 'size:64'], 'page_id' => ['required', 'string', 'max:100'], 'platform' => ['required', Rule::in(['facebook', 'instagram'])]]);
        $data = $this->result($r, $business, $d['connection_key']);
        $p = collect($data['pages'])->firstWhere('id', $d['page_id']);
        abort_unless($p, 422, 'Choose an authorized page.');
        $id = $d['platform'] === 'instagram' ? ($p['instagram_business_account']['id'] ?? null) : $p['id'];
        abort_unless($id, 422, 'No professional Instagram account is linked to this page.');
        $account = DB::transaction(function () use ($business, $d, $p, $id) {
            Business::whereKey($business->id)->lockForUpdate()->firstOrFail();

            return SocialAccount::updateOrCreate(['business_id' => $business->id, 'platform' => $d['platform'], 'external_id' => $id], ['account_name' => $d['platform'] === 'instagram' ? ($p['instagram_business_account']['username'] ?? $p['name']) : $p['name'], 'access_token' => $p['access_token'], 'refresh_token' => null, 'connection_status' => 'connected', 'metadata' => ['page_id' => $p['id']]]);
        }, 3);

        if (! $r->is('api/*') && ! $r->expectsJson()) {
            return redirect()->route('extensions.workspace', [$business, 'social'])
                ->with('success', ucfirst($account->platform).' account connected: '.$account->account_name);
        }

        return $this->reply($r, $account->toArray(), 201);
    }

    public function disconnect(Request $r, Business $business, SocialAccount $account)
    {
        $this->business($r, $business, 'social.manage');
        abort_unless((int) $account->business_id === (int) $business->id, 404);
        $account->update(['connection_status' => 'disconnected', 'access_token' => null, 'refresh_token' => null]);

        return $this->reply($r, ['disconnected' => true]);
    }

    public function posts(Request $r, Business $business)
    {
        $this->business($r, $business, 'social.view');

        return response()->json(['data' => SocialPost::with('social_post_platforms.social_account', 'social_post_media')->where('business_id', $business->id)->latest('id')->paginate(20)]);
    }

    public function store(Request $r, Business $business)
    {
        $this->business($r, $business, 'social.manage');
        $d = $r->validate(['caption' => ['required', 'string', 'max:2200'], 'account_ids' => ['required', 'array', 'min:1', 'max:10'], 'account_ids.*' => ['required', 'integer', 'distinct', Rule::exists('social_accounts', 'id')->where('business_id', $business->id)->where('connection_status', 'connected')], 'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png', 'max:8192'], 'publish_mode' => ['required', Rule::in(['draft', 'now', 'scheduled'])], 'scheduled_at' => ['required_if:publish_mode,scheduled', 'nullable', 'date', 'after:now'], 'request_key' => ['required', 'string', 'max:100']]);
        if (! empty($d['scheduled_at'])) {
            $d['scheduled_at'] = CarbonImmutable::parse($d['scheduled_at'])->setTimezone(config('app.timezone'));
        }
        if (SocialAccount::whereIn('id', $d['account_ids'])->where('platform', 'instagram')->exists() && ! $r->hasFile('image')) {
            throw ValidationException::withMessages(['image' => 'Instagram posts need an image.']);
        }
        $instagram = SocialAccount::whereIn('id', $d['account_ids'])->where('platform', 'instagram')->exists();
        if ($instagram && $r->hasFile('image') && $r->file('image')->getMimeType() !== 'image/jpeg') {
            throw ValidationException::withMessages(['image' => 'Instagram publishing requires a JPEG image.']);
        }
        $path = null;
        try {
            $post = DB::transaction(function () use ($r, $business, $d, &$path) {
                Business::whereKey($business->id)->lockForUpdate()->firstOrFail();
                $old = SocialPost::where('business_id', $business->id)->where('request_key', $d['request_key'])->first();
                if ($old) {
                    if ($old->caption !== $d['caption']) {
                        throw ValidationException::withMessages(['request_key' => 'Already used for another post.']);
                    }

                    return $old;
                }
                $post = SocialPost::create(['business_id' => $business->id, 'caption' => $d['caption'], 'post_type' => $r->hasFile('image') ? 'image' : 'text', 'publish_mode' => $d['publish_mode'], 'scheduled_at' => $d['scheduled_at'] ?? now(), 'overall_status' => $d['publish_mode'] === 'draft' ? 'draft' : 'queued', 'is_ai_generated' => false]);
                $post->forceFill(['request_key' => $d['request_key'], 'created_by' => $r->user()->id])->save();
                if ($r->hasFile('image')) {
                    $path = $r->file('image')->store('social/'.$business->id, 'public');
                    if (! $path) {
                        throw new \RuntimeException('Image storage failed.');
                    }$post->social_post_media()->create(['media_type' => 'image', 'file_url' => $path, 'sort_order' => 0]);
                }
                foreach ($d['account_ids'] as $id) {
                    $post->social_post_platforms()->create(['social_account_id' => $id, 'status' => $d['publish_mode'] === 'draft' ? 'draft' : 'queued']);
                }

                return $post;
            });
        } catch (\Throwable $e) {
            if ($path) {
                Storage::disk('public')->delete($path);
            }throw $e;
        }

        return $this->reply($r, $post->load('social_post_platforms')->toArray(), 201);
    }

    public function publish(Request $r, Business $business, SocialPost $post)
    {
        $this->business($r, $business, 'social.manage');
        abort_unless((int) $post->business_id === (int) $business->id, 404);
        DB::transaction(function () use ($post, $r) {
            $post = SocialPost::whereKey($post->id)->lockForUpdate()->firstOrFail();
            abort_unless(in_array($post->overall_status, ['draft', 'cancelled'], true), 422, 'Only draft/cancelled posts can be submitted. Failed or uncertain publications require manual review.');
            $post->forceFill(['overall_status' => 'queued', 'scheduled_at' => now(), 'created_by' => $r->user()->id])->save();
            $post->social_post_platforms()->whereIn('status', ['draft', 'cancelled'])->update(['status' => 'queued']);
        });

        return $this->reply($r, ['queued' => true], 202);
    }

    public function cancel(Request $r, Business $business, SocialPost $post)
    {
        $this->business($r, $business, 'social.manage');
        abort_unless((int) $post->business_id === (int) $business->id, 404);
        DB::transaction(function () use ($post) {
            $post = SocialPost::whereKey($post->id)->lockForUpdate()->firstOrFail();
            abort_if($post->social_post_platforms()->whereIn('status', ['processing', 'published', 'unknown'])->exists(), 422, 'Publication started; cancellation cannot be guaranteed.');
            $post->update(['overall_status' => 'cancelled']);
            $post->social_post_platforms()->update(['status' => 'cancelled']);
        });

        return $this->reply($r, ['cancelled' => true]);
    }

    public function schedules(Request $r, Business $business)
    {
        $this->business($r, $business, 'social.view');

        return response()->json(['data' => SocialSchedule::where('business_id', $business->id)->paginate(20)]);
    }

    public function schedule(Request $r, Business $business)
    {
        $this->business($r, $business, 'social.manage');
        $d = $r->validate(['social_account_id' => ['required', 'integer', Rule::exists('social_accounts', 'id')->where('business_id', $business->id)->where('connection_status', 'connected')], 'frequency' => ['required', Rule::in(['daily', 'weekly'])], 'run_time' => ['required', 'date_format:H:i'], 'days_of_week' => ['required_if:frequency,weekly', 'nullable', 'array', 'min:1', 'max:7'], 'days_of_week.*' => ['integer', 'between:0,6', 'distinct'], 'timezone' => ['required', 'timezone'], 'caption' => ['required', 'string', 'max:2200'], 'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png', 'max:8192']]);
        $account = SocialAccount::findOrFail($d['social_account_id']);
        if ($account->platform === 'instagram' && ! $r->hasFile('image')) {
            throw ValidationException::withMessages(['image' => 'Instagram schedules need an image.']);
        }
        if ($account->platform === 'instagram' && $r->hasFile('image') && $r->file('image')->getMimeType() !== 'image/jpeg') {
            throw ValidationException::withMessages(['image' => 'Instagram publishing requires a JPEG image.']);
        }
        $row = new SocialSchedule;
        $row->fill(collect($d)->except(['caption', 'timezone', 'image'])->all());
        $row->forceFill(['business_id' => $business->id, 'caption' => $d['caption'], 'timezone' => $d['timezone'], 'created_by' => $r->user()->id, 'is_active' => true]);
        $path = null;
        try {
            if ($r->hasFile('image')) {
                $path = $r->file('image')->store('social/'.$business->id, 'public');
                $row->forceFill(['image_path' => $path]);
            }$row->next_run_at = app(SocialScheduler::class)->next($row, now());
            $row->save();
        } catch (\Throwable $e) {
            if ($path) {
                Storage::disk('public')->delete($path);
            }throw $e;
        }

        return $this->reply($r, $row->toArray(), 201);
    }

    public function stopSchedule(Request $r, Business $business, SocialSchedule $schedule)
    {
        $this->business($r, $business, 'social.manage');
        abort_unless((int) $schedule->business_id === (int) $business->id, 404);
        $schedule->update(['is_active' => false]);

        return $this->reply($r, ['stopped' => true]);
    }
}
