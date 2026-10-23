<?php

namespace App\Http\Controllers;

use App\Models\NotificationRecipient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class GrowyaNotificationController extends Controller
{
    private function myQuery(Request $request)
    {
        return NotificationRecipient::query()
            ->where('user_id', $request->user()->id)
            ->whereHas('notification', fn ($q) => $q->where('status', 'sent'));
    }

    public function index(Request $request)
    {
        $query = $this->myQuery($request)->with('notification');
        if ($request->query('filter') === 'unread') {
            $query->whereNull('opened_at');
        }
        $items = $query->latest('id')->paginate(20)->withQueryString();

        return view('notifications.index', compact('items'));
    }

    public function open(Request $request, NotificationRecipient $recipient)
    {
        abort_unless((int) $recipient->user_id === (int) $request->user()->id, 404);
        $recipient->load('notification');
        abort_unless($recipient->notification?->status === 'sent', 404);
        if (! $recipient->opened_at) {
            $recipient->forceFill(['opened_at' => now(), 'status' => 'opened'])->save();
        }
        // A browser click acknowledges only a delivery belonging to this recipient.
        if ($delivery = $request->query('delivery')) {
            DB::table('growya_push_deliveries')->where('id', (int) $delivery)
                ->where('recipient_id', $recipient->id)->whereIn('status', ['accepted', 'delivered', 'opened'])
                ->update(['status' => 'opened', 'opened_at' => now(), 'updated_at' => now()]);
        }
        $url = $recipient->notification->action_url;
        if (! is_string($url) || ! str_starts_with($url, '/') || str_starts_with($url, '//') || str_contains($url, '\\') || preg_match('/[\x00-\x1F\x7F]/', $url)) {
            $url = route('growya.notifications.index', absolute: false);
        }

        // Avoid redirecting to a business owned by someone else: those pages
        // already enforce their own authorization. Notification is recipient-specific.
        return redirect($url);
    }

    public function read(Request $request, NotificationRecipient $recipient)
    {
        abort_unless((int) $recipient->user_id === (int) $request->user()->id, 404);
        if (! $recipient->opened_at) {
            $recipient->forceFill(['opened_at' => now(), 'status' => 'opened'])->save();
        }

        return back()->with('success', 'Notification marked as read.');
    }

    public function readAll(Request $request)
    {
        $this->myQuery($request)->whereNull('opened_at')->update([
            'opened_at' => now(), 'status' => 'opened', 'updated_at' => now(),
        ]);

        return back()->with('success', 'All notifications marked as read.');
    }
}
