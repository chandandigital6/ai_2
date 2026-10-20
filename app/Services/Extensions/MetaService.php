<?php

namespace App\Services\Extensions;

use App\Models\SocialAccount;
use Illuminate\Support\Facades\Http;

class MetaService
{
    public function base(): string
    {
        $v = (string) config('growya_extensions.meta.version');
        abort_unless(preg_match('/^v[0-9]+\.[0-9]+$/', $v) && config('growya_extensions.meta.app_id') && config('growya_extensions.meta.secret') && str_starts_with((string) config('growya_extensions.meta.redirect_uri'), 'https://'), 503, 'Meta OAuth is not configured.');

        return 'https://graph.facebook.com/'.$v;
    }

    public function pages(string $code): array
    {
        $base = $this->base();
        $common = ['client_id' => config('growya_extensions.meta.app_id'), 'client_secret' => config('growya_extensions.meta.secret')];
        $r = Http::asForm()->timeout(20)->post($base.'/oauth/access_token', $common + ['redirect_uri' => config('growya_extensions.meta.redirect_uri'), 'code' => $code]);
        if (! $r->successful() || ! $r->json('access_token')) {
            throw new \RuntimeException('Meta authorization failed.');
        }
        $r = Http::asForm()->timeout(20)->post($base.'/oauth/access_token', $common + ['grant_type' => 'fb_exchange_token', 'fb_exchange_token' => $r->json('access_token')]);
        if (! $r->successful() || ! $r->json('access_token')) {
            throw new \RuntimeException('Meta token exchange failed.');
        }
        $token = $r->json('access_token');
        $url = $base.'/me/accounts';
        $pages = [];
        $after = null;
        // Pagination cursor stays on our fixed Graph endpoint; never follow provider-supplied URLs.
        for ($n = 0; $n < 10; $n++) {
            $r = Http::withToken($token)->timeout(20)->get($url, array_filter(['fields' => 'id,name,access_token,instagram_business_account{id,username}', 'limit' => 100, 'after' => $after]));
            if (! $r->successful()) {
                throw new \RuntimeException('Meta page discovery failed.');
            }
            foreach ($r->json('data', []) as $p) {
                if (! empty($p['id']) && ! empty($p['access_token'])) {
                    $pages[] = $p;
                }
            }
            $after = $r->json('paging.cursors.after');
            if (! $r->json('paging.next') || ! $after) {
                break;
            }
        }

        return $pages;
    }

    public function publish(SocialAccount $account, string $caption, ?string $imageUrl, ?string $containerId = null): array
    {
        $base = $this->base();
        $id = (string) $account->external_id;
        if (! preg_match('/^[0-9]+$/', $id)) {
            throw new \RuntimeException('Invalid social account.');
        }
        $http = Http::withToken($account->access_token)->timeout(35)->withOptions(['allow_redirects' => false]);
        if ($account->platform === 'facebook') {
            $r = $http->asForm()->post($base.'/'.$id.($imageUrl ? '/photos' : '/feed'), $imageUrl ? ['url' => $imageUrl, 'caption' => $caption] : ['message' => $caption]);
            if (! $r->successful()) {
                return ['status' => in_array($r->status(), [401, 403], true) ? 'reconnect_required' : 'failed'];
            }
            $postId = $r->json('post_id') ?: $r->json('id');

            return ['status' => 'published', 'external_id' => $postId, 'url' => $postId ? 'https://www.facebook.com/'.$postId : null];
        }
        if (! $imageUrl) {
            throw new \RuntimeException('Instagram requires an image.');
        }
        if (! $containerId) {
            $r = $http->asForm()->post($base.'/'.$id.'/media', ['image_url' => $imageUrl, 'caption' => $caption]);
            if (! $r->successful() || ! $r->json('id')) {
                return ['status' => 'failed'];
            }

            return ['status' => 'container_created', 'container_id' => $r->json('id')];
        }
        $r = $http->get($base.'/'.rawurlencode($containerId), ['fields' => 'status_code']);
        if (! $r->successful()) {
            return ['status' => 'failed'];
        }
        if ($r->json('status_code') === 'IN_PROGRESS') {
            return ['status' => 'waiting'];
        }
        if ($r->json('status_code') !== 'FINISHED') {
            return ['status' => 'failed'];
        }
        $r = $http->asForm()->post($base.'/'.$id.'/media_publish', ['creation_id' => $containerId]);
        if (! $r->successful() || ! $r->json('id')) {
            return ['status' => 'failed'];
        }

        return ['status' => 'published', 'external_id' => $r->json('id'), 'url' => null];
    }
}
