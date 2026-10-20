<?php

namespace App\Jobs\Extensions;

use App\Models\NotificationRecipient;
use App\Models\UserDevice;
use App\Services\Extensions\FcmService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Support\Facades\DB;

class SendPush implements ShouldQueue
{
    use Queueable;

    public int $tries = 4;

    public int $timeout = 45;

    public function __construct(public int $deliveryId)
    {
        $this->onQueue('notifications');
    }

    public function backoff(): array
    {
        return [30, 120, 600];
    }

    public function middleware(): array
    {
        return [(new WithoutOverlapping('push:'.$this->deliveryId))->releaseAfter(30)->expireAfter(90)];
    }

    public function handle(FcmService $fcm): void
    {
        if (! $fcm->enabled()) {
            return;
        }
        $row = DB::table('growya_push_deliveries')->find($this->deliveryId);
        if (! $row || ! in_array($row->status, ['queued', 'retry'], true)) {
            return;
        }
        $device = UserDevice::find($row->device_id);
        $recipient = NotificationRecipient::with('notification', 'user')->find($row->recipient_id);
        if (! $device || ! $recipient || ! $device->is_active || (int) $device->user_id !== (int) $recipient->user_id || $recipient->user?->status !== 'active') {
            DB::table('growya_push_deliveries')->where('id', $row->id)->update(['status' => 'skipped', 'updated_at' => now()]);

            return;
        }
        DB::table('growya_push_deliveries')->where('id', $row->id)->increment('attempts');
        try {
            $result = $fcm->send($device->fcm_token, ['title' => $recipient->notification->title, 'body' => $recipient->notification->message], ['recipient_id' => $recipient->id, 'delivery_id' => $row->id, 'action_url' => $recipient->notification->action_url ?? '']);
        } catch (\Throwable $e) {
            DB::table('growya_push_deliveries')->where('id', $row->id)->update(['status' => 'retry', 'error_code' => 'TRANSPORT_OR_CONFIG', 'updated_at' => now()]);
            throw new \RuntimeException('FCM transport/configuration failure.'); // Never log provider tokens or HTTP request contents.
        }
        DB::table('growya_push_deliveries')->where('id', $row->id)->update(['status' => $result['ok'] ? 'accepted' : ($result['retry'] ? 'retry' : 'failed'), 'provider_message_id' => $result['id'], 'error_code' => $result['ok'] ? null : $result['code'], 'accepted_at' => $result['ok'] ? now() : null, 'updated_at' => now()]);
        if ($result['invalid']) {
            $device->update(['is_active' => false]);
        }
        if ($result['retry']) {
            throw new \RuntimeException('Transient FCM error.');
        }
    }

    public function failed(?\Throwable $e): void
    {
        DB::table('growya_push_deliveries')->where('id', $this->deliveryId)->whereIn('status', ['queued', 'retry'])->update(['status' => 'failed', 'error_code' => 'RETRY_EXHAUSTED', 'updated_at' => now()]);
    }
}
