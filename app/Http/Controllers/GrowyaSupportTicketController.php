<?php

namespace App\Http\Controllers;

use App\Models\{Business, GrowyaSupportTicket, GrowyaSupportMessage, GrowyaSupportAttachment, GrowyaSupportEvent, User};
use App\Services\GrowyaNotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{DB, Storage};
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class GrowyaSupportTicketController extends Controller
{
    private function admins(User $user): bool
    {
        return $user->hasRole('super_admin', 'web');
    }
    private function support(User $user): bool
    {
        return $this->admins($user) || $user->can('support.tickets.manage');
    }
    private function access(User $user, GrowyaSupportTicket $ticket): bool
    {
        return (int) $ticket->user_id === (int) $user->id
            || ($this->support($user) && ($this->admins($user) || (int) $ticket->assigned_to === (int) $user->id));
    }
    private function notify(array $ids, GrowyaSupportTicket $t, string $title, string $msg): void
    {
        app(GrowyaNotificationService::class)->send($ids, 'support_ticket', $title, $msg, route('growya.support.show', $t, false), $t->business_id);
    }
    private function log(GrowyaSupportTicket $t, ?int $actor, string $event, ?string $old = null, ?string $new = null): void
    {
        GrowyaSupportEvent::create(['ticket_id' => $t->id, 'actor_id' => $actor, 'event' => $event, 'old_value' => $old, 'new_value' => $new]);
    }
    private function upload(GrowyaSupportMessage $msg, Request $request): void
    {
        foreach ($request->file('attachments', []) as $file) {
            $path = $file->store('growya-support/' . $msg->ticket_id, 'local');
            GrowyaSupportAttachment::create(['message_id' => $msg->id, 'disk' => 'local', 'path' => $path, 'original_name' => Str::limit(basename($file->getClientOriginalName()), 255, ''), 'mime_type' => $file->getMimeType(), 'size' => $file->getSize()]);
        }
    }
    public function index(Request $request)
    {
        $user = $request->user();
        $admin = $this->admins($user);
        $support = $this->support($user);
        $q = GrowyaSupportTicket::with(['creator:id,name', 'assignee:id,name', 'business:id,name'])->latest();
        if (!$admin) {
            $q->where(function ($q) use ($user, $support) {
                $q->where('user_id', $user->id);
                if ($support) {
                    $q->orWhere('assigned_to', $user->id);
                }
            });
        }
        if ($request->filled('status')) {
            $request->validate(['status' => Rule::in(['open', 'in_progress', 'resolved', 'closed'])]);
            $q->where('status', $request->status);
        }
        if ($request->filled('search')) {
            $request->validate(['search' => 'string|max:100']);
            $term = trim($request->search);
            $q->where(fn($q) => $q->where('ticket_number', 'like', "%{$term}%")->orWhere('subject', 'like', "%{$term}%"));
        }
        return view('growya-support.index', ['tickets' => $q->paginate(20)->withQueryString(), 'admin' => $admin, 'support' => $support]);
    }
    public function create(Request $request)
    {
        $businesses = Business::query()->where('owner_id', $request->user()->id)->where('status', 'active')->orderBy('name')->get(['id', 'name']);
        return view('growya-support.create', compact('businesses'));
    }
    public function store(Request $request)
    {
        $data = $request->validate(['subject' => 'required|string|max:200', 'category' => ['required', Rule::in(['orders', 'payments', 'refunds', 'business', 'technical', 'other'])], 'priority' => ['required', Rule::in(['low', 'medium', 'high', 'urgent'])], 'message' => 'required|string|min:5|max:10000', 'business_id' => 'nullable|integer|exists:businesses,id', 'attachments' => 'nullable|array|max:3', 'attachments.*' => 'file|mimes:jpg,jpeg,png,webp,pdf|max:5120']);
        if (!empty($data['business_id'])) {
            abort_unless(Business::whereKey($data['business_id'])->where('owner_id', $request->user()->id)->exists() || $this->admins($request->user()), 403);
        }
        $t = DB::transaction(function () use ($request, $data) {
            $t = GrowyaSupportTicket::create(['ticket_number' => 'GYS-' . strtoupper((string)Str::ulid()), 'user_id' => $request->user()->id, 'business_id' => $data['business_id'] ?? null, 'subject' => $data['subject'], 'category' => $data['category'], 'priority' => $data['priority'], 'status' => 'open', 'last_reply_at' => now()]);
            $m = GrowyaSupportMessage::create(['ticket_id' => $t->id, 'user_id' => $request->user()->id, 'message' => $data['message']]);
            $this->upload($m, $request);
            $this->log($t, $request->user()->id, 'created');
            return $t;
        });
        $this->notify(app(GrowyaNotificationService::class)->superAdmins(), $t, 'New support ticket', $t->ticket_number . ' - ' . $t->subject);
        return redirect()->route('growya.support.show', $t)->with('success', 'Support ticket created.');
    }
    public function show(Request $request, GrowyaSupportTicket $ticket)
    {
        $user = $request->user();
        abort_unless($this->access($user, $ticket), 403);
        $isSupport = $this->support($user);

        $ticket->load([
            'creator:id,name,email',
            'assignee:id,name',
            'business:id,name',
            // Internal notes sirf support staff ke liye load hote hain; customer tak data pahunchta hi nahi.
            'messages' => fn($q) => $q->with(['user:id,name', 'attachments'])
                ->when(!$isSupport, fn($q) => $q->where('is_internal', false))
                ->oldest(),
            'events' => fn($q) => $q->with('actor:id,name')->oldest(),
        ]);
        $agents = $this->admins($user) ? User::permission('support.tickets.manage')->where('status', 'active')->get(['id', 'name']) : collect();
        return view('growya-support.show', ['ticket' => $ticket, 'admin' => $this->admins($user), 'support' => $isSupport, 'agents' => $agents]);
    }
    public function reply(Request $request, GrowyaSupportTicket $ticket)
    {
        $user = $request->user();
        abort_unless($this->access($user, $ticket), 403);
        abort_if($ticket->status === 'closed', 422, 'Closed tickets cannot receive replies.');
        $data = $request->validate(['message' => 'required|string|min:1|max:10000', 'is_internal' => 'sometimes|boolean', 'attachments' => 'nullable|array|max:3', 'attachments.*' => 'file|mimes:jpg,jpeg,png,webp,pdf|max:5120']);
        $internal = $this->support($user) && (bool)($data['is_internal'] ?? false);
        $isCustomer = (int) $user->id === (int) $ticket->user_id;

        DB::transaction(function () use ($request, $ticket, $data, $internal, $isCustomer, $user) {
            $m = GrowyaSupportMessage::create(['ticket_id' => $ticket->id, 'user_id' => $user->id, 'message' => $data['message'], 'is_internal' => $internal]);
            $this->upload($m, $request);

            $changes = ['last_reply_at' => now()];
            // Customer ne resolved ticket par reply kiya to ticket wapas open ho jaye.
            if ($isCustomer && !$internal && $ticket->status === 'resolved') {
                $this->log($ticket, $user->id, 'status_changed', 'resolved', 'open');
                $changes += ['status' => 'open', 'resolved_at' => null];
            }
            $ticket->update($changes);
            $this->log($ticket, $user->id, $internal ? 'internal_note' : 'reply');
        });

        if (!$internal) {
            $ids = $isCustomer
                ? array_unique(array_merge(app(GrowyaNotificationService::class)->superAdmins(), array_filter([$ticket->assigned_to])))
                : [$ticket->user_id];
            $this->notify($ids, $ticket, 'Support ticket reply', $ticket->ticket_number . ' received a new reply.');
        }
        return back()->with('success', 'Reply saved.');
    }
    public function update(Request $request, GrowyaSupportTicket $ticket)
    {
        $user = $request->user();
        abort_unless($this->support($user) && ($this->admins($user) || (int) $ticket->assigned_to === (int) $user->id), 403);
        $data = $request->validate(['status' => ['required', Rule::in(['open', 'in_progress', 'resolved', 'closed'])], 'priority' => ['required', Rule::in(['low', 'medium', 'high', 'urgent'])]]);

        $statusChanged = $ticket->status !== $data['status'];
        $priorityChanged = $ticket->priority !== $data['priority'];

        // Kuch nahi badla: na save, na activity log, na customer ko notification.
        if (!$statusChanged && !$priorityChanged) {
            return back()->with('success', 'No changes to save.');
        }

        DB::transaction(function () use ($ticket, $user, $data, $statusChanged, $priorityChanged) {
            if ($statusChanged) {
                $this->log($ticket, $user->id, 'status_changed', $ticket->status, $data['status']);
            }
            if ($priorityChanged) {
                $this->log($ticket, $user->id, 'priority_changed', $ticket->priority, $data['priority']);
            }

            $changes = ['status' => $data['status'], 'priority' => $data['priority']];

            // resolved_at / closed_at sirf status badalne par update hon; warna purani date overwrite ho jati hai.
            if ($statusChanged) {
                $changes['resolved_at'] = $data['status'] === 'resolved' ? now() : null;
                $changes['closed_at'] = $data['status'] === 'closed' ? now() : null;
            }

            $ticket->update($changes);
        });

        // Customer ko sirf status change ki notification chahiye. Priority internal cheez hai.
        if ($statusChanged) {
            $this->notify([$ticket->user_id], $ticket, 'Ticket updated', $ticket->ticket_number . ' status: ' . str_replace('_', ' ', $ticket->status));
        }
        return back()->with('success', 'Ticket updated.');
    }
    public function assign(Request $request, GrowyaSupportTicket $ticket)
    {
        abort_unless($this->admins($request->user()), 403);
        $data = $request->validate(['assigned_to' => 'nullable|integer|exists:users,id']);
        $newId = !empty($data['assigned_to']) ? (int) $data['assigned_to'] : null;

        if ($newId) {
            abort_unless(User::findOrFail($newId)->can('support.tickets.manage'), 422, 'User has no support permission.');
        }

        $oldId = $ticket->assigned_to ? (int) $ticket->assigned_to : null;
        if ($oldId === $newId) {
            return back()->with('success', 'Assignment unchanged.');
        }

        DB::transaction(function () use ($ticket, $request, $oldId, $newId) {
            $ticket->update(['assigned_to' => $newId]);
            $this->log($ticket, $request->user()->id, 'assigned', $oldId ? (string) $oldId : null, $newId ? (string) $newId : null);
        });

        if ($newId) {
            $this->notify([$newId], $ticket, 'Support ticket assigned', $ticket->ticket_number . ' assigned to you.');
        }
        return back()->with('success', 'Assignment saved.');
    }
    public function attachment(Request $request, GrowyaSupportTicket $ticket, GrowyaSupportAttachment $attachment)
    {
        abort_unless($this->access($request->user(), $ticket), 403);
        $msg = $attachment->message;
        abort_unless($msg && (int) $msg->ticket_id === (int) $ticket->id, 404);
        abort_if($msg->is_internal && !$this->support($request->user()), 403);
        abort_unless(Storage::disk($attachment->disk)->exists($attachment->path), 404);
        return Storage::disk($attachment->disk)->download($attachment->path, $attachment->original_name);
    }
}
