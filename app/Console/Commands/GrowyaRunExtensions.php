<?php

namespace App\Console\Commands;

use App\Jobs\Extensions\GenerateAi;
use App\Jobs\Extensions\PublishSocial;
use App\Jobs\Extensions\SendPush;
use App\Models\AiGeneration;
use App\Models\NotificationRecipient;
use App\Models\SocialPostPlatform;
use App\Services\Extensions\AnalyticsService;
use App\Services\Extensions\PushService;
use App\Services\Extensions\SocialScheduler;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class GrowyaRunExtensions extends Command
{
    protected $signature = 'growya:extensions {--aggregate=}';

    protected $description = 'Run pending module outboxes, recurring schedules and optional daily analytics';

    public function handle(): int
    {
        if ($date = $this->option('aggregate')) {
            $valid = \DateTimeImmutable::createFromFormat('!Y-m-d', $date);
            if (! $valid || $valid->format('Y-m-d') !== $date) {
                $this->error('Use YYYY-MM-DD.');

                return self::FAILURE;
            }
            app(AnalyticsService::class)->aggregate($date);

            return self::SUCCESS;
        }
        app(SocialScheduler::class)->tick();
        foreach (AiGeneration::where('status', 'queued')->where('created_at', '<', now()->subMinute())->limit(100)->pluck('id') as $id) {
            GenerateAi::dispatch($id);
        }
        // Workers may die without running failed(). Recover funds, but never automatically reissue the paid AI request.
        foreach (AiGeneration::where('status', 'processing')->where('updated_at', '<', now()->subMinutes(15))->limit(100)->pluck('id') as $id) {
            (new GenerateAi($id))->failed(null);
        }
        foreach (SocialPostPlatform::whereIn('status', ['queued', 'waiting'])->whereHas('social_post', fn ($q) => $q->where('overall_status', 'queued')->where('scheduled_at', '<=', now()))->where('updated_at', '<', now()->subSeconds(45))->limit(100)->pluck('id') as $id) {
            PublishSocial::dispatch($id);
        }
        foreach (SocialPostPlatform::where('status', 'processing')->where('updated_at', '<', now()->subMinutes(10))->limit(100)->pluck('id') as $id) {
            (new PublishSocial($id))->failed(null);
        }
        if (config('growya_extensions.fcm.enabled')) {
            foreach (NotificationRecipient::where('created_at', '>=', now()->subDay())->where('created_at', '<', now()->subMinute())->whereNull('opened_at')->orderByDesc('id')->limit(500)->pluck('id') as $id) {
                app(PushService::class)->enqueue($id);
            }
            foreach (DB::table('growya_push_deliveries')->whereIn('status', ['queued', 'retry'])->where('updated_at', '<', now()->subMinutes(15))->limit(100)->pluck('id') as $id) {
                SendPush::dispatch($id);
            }
        }
        DB::table('otp_codes')->where('created_at', '<', now()->subDays(7))->delete();
        $this->info('Pending module outboxes processed.');

        return self::SUCCESS;
    }
}
