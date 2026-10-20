<?php

namespace App\Jobs\Extensions;

use App\Models\Business;
use App\Models\SocialAccount;
use App\Models\SocialPost;
use App\Models\SocialPostPlatform;
use App\Models\User;
use App\Services\BusinessAccessService;
use App\Services\Extensions\MetaService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class PublishSocial implements ShouldQueue
{
    use Queueable;

    public int $tries = 1;

    public int $timeout = 90;

    public bool $failOnTimeout = true;

    public function __construct(public int $platformId)
    {
        $this->onQueue('social');
    }

    public function middleware(): array
    {
        return [(new WithoutOverlapping('social:'.$this->platformId))->dontRelease()->expireAfter(180)];
    }

    public function handle(MetaService $meta): void
    {
        $row = SocialPostPlatform::find($this->platformId);
        if (! $row) {
            return;
        }
        $claimed = DB::transaction(function () use ($row) {
            $post = SocialPost::whereKey($row->social_post_id)->lockForUpdate()->firstOrFail();
            if ($post->overall_status !== 'queued' || $post->scheduled_at?->isFuture()) {
                return false;
            }

            return (bool) SocialPostPlatform::whereKey($row->id)->whereIn('status', ['queued', 'waiting'])->update(['status' => 'processing']);
        });
        if (! $claimed) {
            return;
        }
        $row->refresh();
        $post = SocialPost::with('social_post_media')->findOrFail($row->social_post_id);
        $account = SocialAccount::find($row->social_account_id);
        $user = User::find($post->created_by);
        $b = Business::find($post->business_id);
        if (! $user || ! $b || ! app(BusinessAccessService::class)->can($user, $b, 'social.manage') || ! $account || $account->connection_status !== 'connected' || (int) $account->business_id !== (int) $b->id) {
            $row->update(['status' => 'failed', 'error' => 'Access revoked or account disconnected.']);
            $this->summarize($post);

            return;
        }
        try {
            $media = $post->social_post_media->first();
            $url = $media ? Storage::disk('public')->url($media->file_url) : null;
            if ($url && ! str_starts_with($url, 'https://')) {
                throw new \RuntimeException('Public HTTPS media origin required.');
            }
            $result = $meta->publish($account, $post->caption ?? '', $url, $row->container_id);
            if ($result['status'] === 'container_created') {
                $row->forceFill(['container_id' => $result['container_id'], 'status' => 'waiting'])->save();
            } elseif ($result['status'] === 'waiting') {
                $row->update(['status' => 'waiting']);
            } elseif ($result['status'] === 'published') {
                $row->update(['status' => 'published', 'external_post_id' => $result['external_id'], 'external_post_url' => $result['url'], 'published_at' => now(), 'error' => null]);
            } else {
                $row->update(['status' => 'failed', 'error' => 'Provider rejected publication. Check account and approved permissions.']);
                if ($result['status'] === 'reconnect_required') {
                    $account->update(['connection_status' => 'reconnect_required']);
                }
            }
        } catch (\Throwable $e) {
            $row->update(['status' => 'unknown', 'error' => 'Publication outcome is uncertain. Check the platform before creating another post.']);
        }
        $this->summarize($post);
    }

    private function summarize(SocialPost $post): void
    {
        DB::transaction(function () use ($post) {
            $post = SocialPost::whereKey($post->id)->lockForUpdate()->firstOrFail();
            $statuses = $post->social_post_platforms()->pluck('status');
            $status = $statuses->every(fn ($s) => $s === 'published') ? 'published' : ($statuses->contains(fn ($s) => in_array($s, ['queued', 'waiting', 'processing'], true)) ? 'queued' : ($statuses->contains('published') ? 'partial' : 'failed'));
            $post->update(['overall_status' => $status]);
        });
    }

    public function failed(?\Throwable $e): void
    {
        $row = SocialPostPlatform::find($this->platformId);
        if ($row && $row->status === 'processing') {
            $row->update(['status' => 'unknown', 'error' => 'Worker stopped during publication; reconcile manually.']);
            $this->summarize(SocialPost::findOrFail($row->social_post_id));
        }
    }
}
