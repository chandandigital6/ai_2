<?php

namespace App\Http\Controllers;

use App\Models\NotificationRecipient;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GrowyaLiveNotificationController extends Controller
{
    public function poll(Request $request): JsonResponse
    {
        $query = NotificationRecipient::query()
            ->where('user_id', $request->user()->getKey())
            ->whereNull('opened_at')
            ->whereHas('notification', fn ($q) => $q->where('status', 'sent'));

        $count = (clone $query)->count();
        $items = (clone $query)->with('notification:id,title,message,status')
            ->orderByDesc('id')->limit(8)->get();

        return response()->json([
            'count' => $count,
            'latest_id' => (int) ($items->first()?->id ?? 0),
            'items' => $items->map(fn ($item) => [
                'id' => $item->id,
                'title' => $item->notification?->title ?? 'Notification',
                'message' => $item->notification?->message ?? '',
                'created_at' => $item->created_at?->diffForHumans(),
                'open_url' => route('growya.notifications.open', $item),
            ])->values(),
        ])->header('Cache-Control', 'private, no-store, max-age=0');
    }
}
