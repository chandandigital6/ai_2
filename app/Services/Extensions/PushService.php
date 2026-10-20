<?php

namespace App\Services\Extensions;

use App\Jobs\Extensions\SendPush;
use App\Models\NotificationRecipient;
use App\Models\UserDevice;
use Illuminate\Support\Facades\DB;

class PushService
{
    public function enqueue(int $recipientId): void
    {
        if (! config('growya_extensions.fcm.enabled')) {
            return;
        }
        $recipient = NotificationRecipient::find($recipientId);
        if (! $recipient || $recipient->user?->status !== 'active') {
            return;
        }
        foreach (UserDevice::where('user_id', $recipient->user_id)->where('is_active', true)->get() as $device) {
            DB::table('growya_push_deliveries')->insertOrIgnore(['recipient_id' => $recipientId, 'device_id' => $device->id, 'status' => 'queued', 'attempts' => 0, 'created_at' => now(), 'updated_at' => now()]);
        }
        // Scheduler also sweeps the outbox, recovering broker failures without resending accepted rows.
        foreach (DB::table('growya_push_deliveries')->where('recipient_id', $recipientId)->where('status', 'queued')->pluck('id') as $id) {
            SendPush::dispatch($id)->afterCommit();
        }
    }
}
