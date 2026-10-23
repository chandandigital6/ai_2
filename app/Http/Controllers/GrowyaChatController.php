<?php

namespace App\Http\Controllers;

use App\Models\Business;
use App\Models\GrowyaChatActivityLog;
use App\Models\GrowyaChatMessage;
use App\Models\GrowyaChatThread;
use App\Models\Product;
use App\Models\Service;
use App\Models\User;
use App\Services\BusinessAccessService;
use App\Services\GrowyaNotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class GrowyaChatController extends Controller
{
    private function isStaffAllowed(Business $business, string $permission): bool
    {
        $user = auth()->user();
        if (! $user || $user->status !== 'active') {
            return false;
        }
        if ($business->status !== 'active' || $business->verification_status !== 'approved') {
            return false;
        }
        if ($user->hasRole('super_admin', 'web') || (int) $business->owner_id === (int) $user->id) {
            return true;
        }
        $member = app(BusinessAccessService::class)->membership($user, $business);

        return $member && $member->hasBusinessPermission($permission);
    }

    private function isParticipant(GrowyaChatThread $thread, bool $write = false): bool
    {
        if ((int) $thread->customer_id === (int) auth()->id()) {
            return true;
        }
        if (! $this->isStaffAllowed($thread->business, $write ? 'chats.reply' : 'chats.view')) {
            return false;
        }
        $isOwner = auth()->user()->hasRole('super_admin', 'web') || (int) $thread->business->owner_id === (int) auth()->id();

        return $isOwner || $thread->assigned_to === null || (int) $thread->assigned_to === (int) auth()->id();
    }

    private function assertParticipant(GrowyaChatThread $thread, bool $write = false): void
    {
        abort_unless($this->isParticipant($thread, $write), 403);
    }

    public function mine()
    {
        $threads = GrowyaChatThread::query()->where('customer_id', auth()->id())
            ->with(['business:id,name,slug', 'latestMessage'])
            ->when(request('search'), function ($q) {
                $term = trim((string) request('search'));
                $q->whereHas('business', fn ($b) => $b->where('name', 'like', '%'.$term.'%'));
            })
            ->orderByDesc('last_message_at')->paginate(20);

        return view('growya-chat.list', ['threads' => $threads, 'heading' => 'My messages', 'business' => null]);
    }

    public function inbox(Business $business)
    {
        abort_unless($this->isStaffAllowed($business, 'chats.view'), 403);
        $threads = GrowyaChatThread::query()->where('business_id', $business->id)
            ->with(['customer:id,name', 'business:id,name', 'latestMessage'])
            ->when(! auth()->user()->hasRole('super_admin', 'web') && (int) $business->owner_id !== (int) auth()->id(), fn ($q) => $q->where(fn ($w) => $w->whereNull('assigned_to')->orWhere('assigned_to', auth()->id())))
            ->when(request('search'), fn ($q) => $q->whereHas('customer', fn ($c) => $c->where('name', 'like', '%'.trim((string) request('search')).'%')))
            ->orderByDesc('last_message_at')->paginate(20);

        return view('growya-chat.list', ['threads' => $threads, 'heading' => 'Business inbox', 'business' => $business]);
    }

    public function start(Request $request, Business $business)
    {
        abort_unless($business->status === 'active' && $business->verification_status === 'approved', 404);
        abort_if((int) $business->owner_id === (int) auth()->id(), 422, 'You cannot start a customer chat with your own business.');
        $data = $request->validate([
            'context_type' => ['required', Rule::in(['general', 'product', 'service'])],
            'context_id' => ['nullable', 'integer', 'min:1'],
        ]);
        $type = $data['context_type'];
        $id = $type === 'general' ? 0 : (int) ($data['context_id'] ?? 0);
        if ($type !== 'general') {
            abort_unless($id > 0, 422);
            $model = $type === 'product' ? Product::class : Service::class;
            abort_unless($model::query()->whereKey($id)->where('business_id', $business->id)->where('status', 'active')->exists(), 404);
        }
        $thread = GrowyaChatThread::query()->firstOrCreate([
            'business_id' => $business->id,
            'customer_id' => auth()->id(),
            'context_type' => $type,
            'context_id' => $id,
        ]);

        return redirect()->route('growya.chat.show', $thread);
    }

    public function show(GrowyaChatThread $thread)
    {
        $thread->load('business', 'customer');
        $this->assertParticipant($thread);
        // Read markers belong to recipients, not senders.
        GrowyaChatMessage::where('thread_id', $thread->id)
            ->where('sender_id', '!=', auth()->id())->whereNull('read_at')->update(['read_at' => now()]);
        $thread->update([(int) $thread->customer_id === (int) auth()->id() ? 'customer_read_at' : 'business_read_at' => now()]);
        $messages = $thread->messages()->with('sender:id,name')->latest('id')->paginate(40);

        return view('growya-chat.show', compact('thread', 'messages'));
    }

    public function poll(GrowyaChatThread $thread, Request $request)
    {
        $this->assertParticipant($thread);
        $after = max(0, (int) $request->query('after', 0));
        $messages = $thread->messages()->where('id', '>', $after)->orderBy('id')->limit(50)->get();
        $thread->messages()->whereIn('id', $messages->pluck('id'))->where('sender_id', '!=', auth()->id())->whereNull('read_at')->update(['read_at' => now()]);
        $thread->update([(int) $thread->customer_id === (int) auth()->id() ? 'customer_read_at' : 'business_read_at' => now()]);

        return response()->json(['messages' => $messages->map(fn ($m) => [
            'id' => $m->id, 'mine' => (int) $m->sender_id === (int) auth()->id(),
            'body' => $m->body, 'attachment' => $m->file_path ? route('growya.chat.file', [$thread, $m]) : null,
            'time' => $m->created_at->format('d M, H:i'),
        ])]);
    }

    public function send(Request $request, GrowyaChatThread $thread, GrowyaNotificationService $notifications)
    {
        $thread->load('business');
        $this->assertParticipant($thread, true);
        abort_if($thread->customer_blocked || $thread->business_blocked, 403, 'This conversation is blocked.');
        $data = $request->validate([
            'body' => ['nullable', 'string', 'max:5000', 'required_without:attachment'],
            'attachment' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:5120', 'required_without:body'],
        ]);
        $path = null;
        $file = $request->file('attachment');
        if ($file) {
            $path = $file->store('growya-private-chat/'.$thread->id, 'local');
        }
        try {
            $message = DB::transaction(function () use ($thread, $data, $file, $path) {
                $message = GrowyaChatMessage::create([
                    'thread_id' => $thread->id,
                    'sender_id' => auth()->id(),
                    'body' => isset($data['body']) ? trim($data['body']) : null,
                    'file_path' => $path,
                    'original_filename' => $file ? basename($file->getClientOriginalName()) : null,
                    'file_mime' => $file?->getMimeType(),
                    'file_size' => $file?->getSize(),
                ]);
                $thread->update(['last_message_at' => now()]);

                return $message;
            });
        } catch (\Throwable $e) {
            if ($path) {
                Storage::disk('local')->delete($path);
            }
            throw $e;
        }
        GrowyaChatActivityLog::create(['thread_id' => $thread->id, 'actor_id' => auth()->id(), 'action' => 'message_sent', 'detail' => $message->file_path ? 'Sent attachment' : 'Sent message']);
        $senderIsCustomer = (int) $thread->customer_id === (int) auth()->id();
        $recipients = $senderIsCustomer ? [(int) $thread->business->owner_id] : [(int) $thread->customer_id];
        if ($senderIsCustomer && $thread->assigned_to) {
            $assigned = User::find($thread->assigned_to);
            if ($assigned && app(BusinessAccessService::class)->can($assigned, $thread->business, 'chats.view')) {
                $recipients[] = (int) $assigned->id;
            }
        }
        $recipients = array_values(array_diff(array_unique($recipients), [(int) auth()->id()]));
        try {
            $notifications->send($recipients, 'chat_message', auth()->user()->name,
                Str::limit($message->body ?: 'Sent an attachment', 160),
                route('growya.chat.show', $thread, false), (int) $thread->business_id);
        } catch (\Throwable $e) {
            report($e);
        }

        return redirect()->route('growya.chat.show', $thread)->with('success', 'Message sent.');
    }

    public function file(GrowyaChatThread $thread, GrowyaChatMessage $message)
    {
        $this->assertParticipant($thread);
        abort_unless((int) $message->thread_id === (int) $thread->id, 404);
        abort_unless($message->file_path && Storage::disk('local')->exists($message->file_path), 404);

        return Storage::disk('local')->download($message->file_path, $message->original_filename ?: 'attachment');
    }
}
