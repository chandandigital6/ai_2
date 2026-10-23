<?php

namespace App\Console\Commands;

use App\Models\UserDevice;
use App\Services\Extensions\FcmService;
use App\Services\Extensions\WebPushConfig;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class GrowyaPushCheck extends Command
{
    protected $signature = 'growya:push-check';

    protected $description = 'Check Firebase push setup locally without sending a message or exposing credentials';

    public function handle(FcmService $fcm, WebPushConfig $web): int
    {
        $valid = true;
        $rows = [['FCM sender enabled', $fcm->enabled() ? 'YES' : 'NO'],
            ['Browser public configuration', $web->ready() ? 'READY' : 'INCOMPLETE'],
            ['Queue connection', config('queue.default')]];
        try {
            $credentials = $fcm->credentials();
            $key = @openssl_pkey_get_private($credentials['private_key']);
            if (! $key) {
                throw new \RuntimeException('The service-account private key is invalid.');
            }
            $rows[] = ['Service-account file and private key', 'OK'];
            $sameProject = ! $web->ready() || $web->firebase()['projectId'] === $credentials['project_id'];
            $rows[] = ['Server / browser Firebase project match', $sameProject ? 'OK' : 'MISMATCH'];
            $valid = $sameProject;
        } catch (\Throwable $e) {
            $rows[] = ['Service-account file', 'MISSING OR INVALID'];
            $valid = false;
        }
        $rows[] = ['Active web / Android / iOS tokens', implode(' / ', collect(['web', 'android', 'ios'])->map(fn ($platform) => UserDevice::where('is_active', true)->where('platform', $platform)->count())->all())];
        $rows[] = ['Queued / retry / failed pushes', implode(' / ', collect(['queued', 'retry', 'failed'])->map(fn ($status) => DB::table('growya_push_deliveries')->where('status', $status)->count())->all())];
        $this->table(['Check', 'Result'], $rows);
        $this->line('Worker: php artisan queue:work --queue=notifications,ai,social,default --timeout=240 --tries=4');
        $this->line('Browser: sign in, open My notifications, Enable browser alerts, then Send me a test.');
        $this->line('This check does not contact Firebase or send a notification.');

        return $valid && $fcm->enabled() ? self::SUCCESS : self::FAILURE;
    }
}
