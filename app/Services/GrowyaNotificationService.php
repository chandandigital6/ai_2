<?php

namespace App\Services;

use App\Models\Business;
use App\Models\Notification;
use App\Models\NotificationRecipient;
use App\Models\User;
use App\Services\Extensions\PushService;
use Illuminate\Support\Facades\DB;

class GrowyaNotificationService
{
    public function send(array $userIds, string $type, string $title, string $message, ?string $url = null, ?int $businessId = null): void
    {
        $ids = array_values(array_unique(array_filter(array_map('intval', $userIds))));
        if (! $ids) {
            return;
        }

        // Never store arbitrary external URLs for redirect actions.
        if ($url !== null && (! str_starts_with($url, '/') || str_starts_with($url, '//'))) {
            $url = null;
        }

        DB::transaction(function () use ($ids, $type, $title, $message, $url, $businessId) {
            $n = Notification::create([
                'business_id' => $businessId,
                'type' => $type,
                'title' => $title,
                'message' => $message,
                'image' => null,
                'action_url' => $url,
                'target_type' => 'users',
                'scheduled_at' => null,
                'status' => 'sent',
            ]);
            $validIds = User::query()->whereIn('id', $ids)->pluck('id');
            foreach ($validIds as $id) {
                $recipient = NotificationRecipient::create([
                    'notification_id' => $n->id,
                    'user_id' => $id,
                    'status' => 'sent',
                    'sent_at' => now(),
                ]);
                DB::afterCommit(function () use ($recipient) {
                    try {
                        app(PushService::class)->enqueue($recipient->id);
                    } catch (\Throwable $e) {
                        report($e);
                    }
                });
            }
        });
    }

    public function ownerOf(int $businessId): array
    {
        $id = Business::query()->whereKey($businessId)->value('owner_id');

        return $id ? [(int) $id] : [];
    }

    public function superAdmins(): array
    {
        return User::role('super_admin', 'web')->pluck('id')->map(fn ($id) => (int) $id)->all();
    }
}
