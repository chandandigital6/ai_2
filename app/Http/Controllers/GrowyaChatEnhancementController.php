<?php

namespace App\Http\Controllers;

use App\Models\Business;
use App\Models\GrowyaChatActivityLog;
use App\Models\GrowyaChatReport;
use App\Models\GrowyaChatThread;
use App\Models\User;
use App\Services\BusinessAccessService;
use App\Services\GrowyaNotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class GrowyaChatEnhancementController extends Controller
{
    private function admin(): bool
    {
        return auth()->user()?->hasRole('super_admin', 'web') ?? false;
    }

    private function owner(GrowyaChatThread $t): bool
    {
        return $this->admin() || (int) $t->business->owner_id === (int) auth()->id();
    }

    private function customer(GrowyaChatThread $t): bool
    {
        return (int) $t->customer_id === (int) auth()->id();
    }

    private function member(GrowyaChatThread $t): bool
    {
        $u = auth()->user();
        if (! $u || $u->status !== 'active') {
            return false;
        }
        $membership = app(BusinessAccessService::class)->membership($u, $t->business);

        return app(BusinessAccessService::class)->can($u, $t->business, 'chats.view') && ($t->assigned_to === null || (int) $t->assigned_to === (int) $u->id);
    }

    private function participant(GrowyaChatThread $t): void
    {
        $t->loadMissing('business');
        abort_unless($this->customer($t) || $this->owner($t) || $this->member($t), 403);
    }

    private function control(GrowyaChatThread $t): void
    {
        $t->loadMissing('business');
        abort_unless($this->owner($t) || app(BusinessAccessService::class)->can(auth()->user(), $t->business, 'chats.assign'), 403);
    }

    private function log(GrowyaChatThread $t, string $action, ?string $detail = null): void
    {
        GrowyaChatActivityLog::create(['thread_id' => $t->id, 'actor_id' => auth()->id(), 'action' => $action, 'detail' => $detail]);
    }

    public function assign(Request $r, GrowyaChatThread $thread): mixed
    {
        $this->control($thread);
        $data = $r->validate(['assigned_to' => ['nullable', 'integer', 'exists:users,id']]);
        $id = $data['assigned_to'] ?? null;
        if ($id) {
            $u = User::findOrFail($id);
            $m = app(BusinessAccessService::class)->membership($u, $thread->business);
            abort_unless($u->status === 'active' && $m && $m->hasBusinessPermission('chats.view') && $m->hasBusinessPermission('chats.reply'), 422, 'Staff must have active chat view and reply permissions.');
        }
        DB::transaction(function () use ($thread, $id) {
            $thread->update(['assigned_to' => $id]);
            $this->log($thread, 'assigned', $id ? 'Assigned to user #'.$id : 'Unassigned');
        });
        if ($id) {
            try {
                app(GrowyaNotificationService::class)->send([(int) $id], 'chat_assigned', 'Chat assigned', 'A customer conversation was assigned to you.', route('growya.chat.show', $thread, false), (int) $thread->business_id);
            } catch (\Throwable $e) {
                report($e);
            }
        }

        return back()->with('success', 'Assignment updated.');
    }

    public function block(Request $r, GrowyaChatThread $thread): mixed
    {
        $this->participant($thread);
        $customer = $this->customer($thread);
        abort_unless($customer || $this->owner($thread), 403);
        $data = $r->validate(['blocked' => ['required', 'boolean']]);
        $key = $customer ? 'customer_blocked' : 'business_blocked';
        DB::transaction(function () use ($thread, $data, $key) {
            $thread->update([$key => (bool) $data['blocked']]);
            $this->log($thread, $data['blocked'] ? 'blocked' : 'unblocked', $key);
        });

        return back()->with('success', 'Conversation preference updated.');
    }

    public function report(Request $r, GrowyaChatThread $thread): mixed
    {
        $this->participant($thread);
        $data = $r->validate(['reason' => ['required', 'string', 'min:10', 'max:2000']]);
        abort_if(GrowyaChatReport::where('thread_id', $thread->id)->where('reported_by', auth()->id())->where('status', 'pending')->exists(), 422, 'You already have an open report on this conversation.');
        DB::transaction(function () use ($thread, $data) {
            GrowyaChatReport::create(['thread_id' => $thread->id, 'reported_by' => auth()->id(), 'reason' => $data['reason']]);
            $this->log($thread, 'reported', 'Report submitted for moderation');
        });
        try {
            $ids = app(GrowyaNotificationService::class)->superAdmins();
            app(GrowyaNotificationService::class)->send($ids, 'chat_report', 'Chat reported', 'A chat report needs moderation.', route('growya.chat.reports', [], false), (int) $thread->business_id);
        } catch (\Throwable $e) {
            report($e);
        }

        return back()->with('success', 'Report submitted to platform support.');
    }

    public function reports(Request $r): mixed
    {
        abort_unless($this->admin(), 403);
        $reports = GrowyaChatReport::with(['thread.business:id,name', 'reporter:id,name'])->when($r->filled('status'), fn ($q) => $q->where('status', $r->status))->latest()->paginate(25);

        return view('growya-chat.reports', compact('reports'));
    }

    public function review(Request $r, GrowyaChatReport $report): mixed
    {
        abort_unless($this->admin(), 403);
        $data = $r->validate(['status' => ['required', Rule::in(['reviewed', 'dismissed'])], 'admin_note' => ['nullable', 'string', 'max:2000']]);
        DB::transaction(function () use ($report, $data) {
            $report->update(['status' => $data['status'], 'admin_note' => $data['admin_note'] ?? null, 'reviewed_by' => auth()->id(), 'reviewed_at' => now()]);
            $this->log($report->thread, 'report_reviewed', $data['status']);
        });

        return back()->with('success', 'Report review saved.');
    }

    public function followup(Request $r, GrowyaChatThread $thread): mixed
    {
        $this->control($thread);
        $data = $r->validate(['followup_status' => ['required', Rule::in(['new', 'contacted', 'followup', 'converted', 'closed'])], 'followup_at' => ['nullable', 'date'], 'note' => ['nullable', 'string', 'max:2000']]);
        DB::transaction(function () use ($thread, $data) {
            $thread->update(['followup_status' => $data['followup_status'], 'followup_at' => $data['followup_at'] ?? null]);
            $this->log($thread, 'followup', ($data['note'] ?? '').' | '.$data['followup_status']);
        });

        return back()->with('success', 'Follow-up saved.');
    }

    public function activity(GrowyaChatThread $thread): mixed
    {
        $this->control($thread);
        $logs = GrowyaChatActivityLog::with('actor:id,name')->where('thread_id', $thread->id)->latest()->paginate(30);

        return view('growya-chat.activity', compact('thread', 'logs'));
    }

    public function unread(): mixed
    {
        $u = auth()->user();
        $customer = GrowyaChatThread::where('customer_id', $u->id)->whereHas('messages', fn ($q) => $q->whereColumn('growya_chat_messages.sender_id', '!=', 'growya_chat_threads.customer_id')->where(function ($q) {
            $q->whereNull('growya_chat_threads.customer_read_at')->orWhereColumn('growya_chat_messages.created_at', '>', 'growya_chat_threads.customer_read_at');
        }))->count();
        $owned = Business::where('owner_id', $u->id)->pluck('id');
        $seller = GrowyaChatThread::whereIn('business_id', $owned)->whereHas('messages', fn ($q) => $q->whereColumn('growya_chat_messages.sender_id', 'growya_chat_threads.customer_id')->where(function ($q) {
            $q->whereNull('growya_chat_threads.business_read_at')->orWhereColumn('growya_chat_messages.created_at', '>', 'growya_chat_threads.business_read_at');
        }))->count();

        return response()->json(['customer_threads' => $customer, 'owner_threads' => $seller]);
    }
}
