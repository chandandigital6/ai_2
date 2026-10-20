<?php

namespace App\Services\Extensions;

use App\Models\Business;
use App\Models\SocialPost;
use App\Models\SocialSchedule;
use App\Models\User;
use App\Services\BusinessAccessService;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

class SocialScheduler
{
    public function next(SocialSchedule $s, \DateTimeInterface $after): CarbonImmutable
    {
        $local = CarbonImmutable::instance($after)->setTimezone($s->timezone ?? 'Asia/Kolkata');
        for ($i = 0; $i < 8; $i++) {
            $candidate = $local->startOfDay()->addDays($i)->setTimeFromTimeString($s->run_time);
            if ($candidate->gt($local) && ($s->frequency === 'daily' || in_array($candidate->dayOfWeek, array_map('intval', $s->days_of_week ?? []), true))) {
                return $candidate->setTimezone(config('app.timezone'));
            }
        }
        throw new \RuntimeException('No valid recurring schedule day.');
    }

    public function tick(): void
    {
        foreach (SocialSchedule::where('is_active', true)->where('next_run_at', '<=', now())->orderBy('id')->limit(100)->pluck('id') as $id) {
            DB::transaction(function () use ($id) {
                $s = SocialSchedule::whereKey($id)->lockForUpdate()->first();
                if (! $s || ! $s->is_active || ! $s->next_run_at || $s->next_run_at->isFuture()) {
                    return;
                }
                $u = User::find($s->created_by);
                $b = Business::find($s->business_id);
                $a = $s->social_account;
                if (! $u || ! $b || ! app(BusinessAccessService::class)->can($u, $b, 'social.manage') || ! $a || $a->connection_status !== 'connected' || (int) $a->business_id !== (int) $b->id) {
                    $s->update(['is_active' => false]);

                    return;
                }
                $key = 'schedule:'.$s->id.':'.$s->next_run_at->timestamp;
                $post = SocialPost::where('business_id', $b->id)->where('request_key', $key)->first();
                if (! $post) {
                    $post = SocialPost::create(['business_id' => $b->id, 'caption' => $s->caption, 'post_type' => $s->image_path ? 'image' : 'text', 'publish_mode' => 'scheduled', 'scheduled_at' => now(), 'is_ai_generated' => false, 'overall_status' => 'queued']);
                    $post->forceFill(['request_key' => $key, 'created_by' => $u->id])->save();
                    $post->social_post_platforms()->create(['social_account_id' => $a->id, 'status' => 'queued']);
                    if ($s->image_path) {
                        $post->social_post_media()->create(['media_type' => 'image', 'file_url' => $s->image_path, 'sort_order' => 0]);
                    }
                }
                // After downtime, run once and advance from now; do not flood the platform with missed runs.
                $s->update(['last_run_at' => now(), 'next_run_at' => $this->next($s, now())]);
            }, 3);
        }
    }
}
