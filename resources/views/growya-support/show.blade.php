@php
    $statusBadge = [
        'open' => 'bg-blue-50 text-blue-700 dark:bg-blue-950 dark:text-blue-300',
        'in_progress' => 'bg-amber-50 text-amber-700 dark:bg-amber-950 dark:text-amber-300',
        'resolved' => 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300',
        'closed' => 'bg-zinc-100 text-zinc-600 dark:bg-zinc-800 dark:text-zinc-300',
    ];

    $priorityBadge = [
        'low' => 'bg-zinc-100 text-zinc-600 dark:bg-zinc-800 dark:text-zinc-300',
        'medium' => 'bg-blue-50 text-blue-700 dark:bg-blue-950 dark:text-blue-300',
        'high' => 'bg-amber-50 text-amber-700 dark:bg-amber-950 dark:text-amber-300',
        'urgent' => 'bg-red-50 text-red-700 dark:bg-red-950 dark:text-red-300',
    ];

    $label = fn($v) => ucfirst(str_replace('_', ' ', (string) $v));
    $agentNames = $agents->pluck('name', 'id');
    $isClosed = $ticket->status === 'closed';

    $card = 'rounded-2xl border bg-white p-5 shadow-sm dark:border-zinc-700 dark:bg-zinc-900';
    $input = 'w-full rounded-lg border p-2.5 focus:border-emerald-600 focus:outline-none focus:ring-2 focus:ring-emerald-100 dark:border-zinc-700 dark:bg-zinc-900';

    // Assign events me user IDs hote hain, unko naam me badlo
    $eventValue = function ($event, $value) use ($agentNames, $label) {
        if ($value === null || $value === '') {
            return '—';
        }
        if ($event === 'assigned') {
            return $agentNames[(int) $value] ?? 'Agent #' . $value;
        }
        return $label($value);
    };
@endphp

<x-layouts::app :title="$ticket->ticket_number">
    <div id="main-content" class="mx-auto max-w-6xl space-y-6 p-4 md:p-8">
        <a href="{{ route('growya.support.index') }}" wire:navigate
            class="text-sm font-semibold text-emerald-700 underline dark:text-emerald-400">← All tickets</a>

        {{-- Header --}}
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div class="min-w-0">
                <p class="text-sm font-semibold text-zinc-500">{{ $ticket->ticket_number }}</p>
                <h1 class="mt-1 text-2xl font-bold md:text-3xl">{{ $ticket->subject }}</h1>
            </div>

            <div class="flex flex-wrap gap-2 text-xs font-semibold">
                <span class="rounded-full px-3 py-1.5 {{ $statusBadge[$ticket->status] ?? $statusBadge['closed'] }}">{{ $label($ticket->status) }}</span>
                <span class="rounded-full px-3 py-1.5 {{ $priorityBadge[$ticket->priority] ?? $priorityBadge['low'] }}">{{ $label($ticket->priority) }} priority</span>
                <span class="rounded-full bg-zinc-100 px-3 py-1.5 text-zinc-600 dark:bg-zinc-800 dark:text-zinc-300">{{ $label($ticket->category) }}</span>
            </div>
        </div>

        @if (session('success'))
            <div class="rounded-xl bg-emerald-50 p-4 text-sm font-semibold text-emerald-800 dark:bg-emerald-950 dark:text-emerald-200" role="status">
                {{ session('success') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="rounded-xl bg-red-50 p-4 text-sm text-red-800 dark:bg-red-950 dark:text-red-200" role="alert">
                {{ $errors->first() }}
            </div>
        @endif

        <div class="grid gap-6 lg:grid-cols-3">

            {{-- Conversation --}}
            <div class="space-y-6 lg:col-span-2">
                <section class="space-y-4" aria-label="Conversation">
                    @foreach ($ticket->messages as $m)
                        @if (!$m->is_internal || $support)
                            @php $fromCustomer = (int) $m->user_id === (int) $ticket->user_id; @endphp

                            <div class="flex {{ $fromCustomer ? 'justify-start' : 'justify-end' }}">
                                <div class="w-full max-w-[92%] rounded-2xl border p-4 sm:max-w-[85%]
                                    {{ $m->is_internal
                                        ? 'border-amber-300 bg-amber-50 text-zinc-900 dark:border-amber-700'
                                        : ($fromCustomer
                                            ? 'bg-white dark:border-zinc-700 dark:bg-zinc-900'
                                            : 'border-emerald-200 bg-emerald-50 dark:border-emerald-800 dark:bg-emerald-950') }}">

                                    <div class="flex flex-wrap items-center justify-between gap-2 text-xs">
                                        <div class="flex flex-wrap items-center gap-2">
                                            <strong class="text-sm">{{ $m->user?->name ?? 'Deleted user' }}</strong>
                                            @if ($m->is_internal)
                                                <span class="rounded-full bg-amber-200 px-2 py-0.5 font-semibold text-amber-900">Internal note</span>
                                            @elseif (!$fromCustomer)
                                                <span class="rounded-full bg-emerald-200 px-2 py-0.5 font-semibold text-emerald-900">Support</span>
                                            @endif
                                        </div>
                                        <span class="opacity-60">{{ $m->created_at?->format('d M Y, h:i A') }}</span>
                                    </div>

                                    <p class="mt-3 whitespace-pre-wrap break-words text-sm leading-6">{{ $m->message }}</p>

                                    @if ($m->attachments->isNotEmpty())
                                        <div class="mt-3 flex flex-wrap gap-2">
                                            @foreach ($m->attachments as $file)
                                                <a class="inline-flex max-w-full items-center gap-1 truncate rounded-lg border bg-white px-3 py-1.5 text-xs font-semibold text-emerald-700 hover:border-emerald-600 dark:border-zinc-700 dark:bg-zinc-900 dark:text-emerald-400"
                                                    href="{{ route('growya.support.attachment', [$ticket, $file]) }}">
                                                    📎 {{ $file->original_name }}
                                                </a>
                                            @endforeach
                                        </div>
                                    @endif
                                </div>
                            </div>
                        @endif
                    @endforeach
                </section>

                {{-- Reply --}}
                @if (!$isClosed)
                    <form method="POST" action="{{ route('growya.support.reply', $ticket) }}" enctype="multipart/form-data"
                        class="{{ $card }} space-y-4">
                        @csrf
                        <div>
                            <label for="reply-message" class="block font-semibold">Reply</label>
                            <textarea id="reply-message" name="message" rows="4" required maxlength="10000"
                                placeholder="Apna jawab likho..." class="mt-2 {{ $input }}">{{ old('message') }}</textarea>
                            @error('message') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                        </div>

                        @if ($support)
                            <label class="flex items-start gap-2 rounded-lg bg-amber-50 p-3 text-sm text-amber-900">
                                <input type="checkbox" name="is_internal" value="1" class="mt-0.5 rounded border-amber-400 text-amber-600">
                                <span>Internal staff note <span class="block text-xs opacity-75">Customer ko ye dikhega nahi aur notification bhi nahi jayegi.</span></span>
                            </label>
                        @endif

                        <div>
                            <label for="reply-files" class="block text-sm font-medium">
                                Attachments <span class="font-normal text-zinc-500">(max 3 · jpg/png/webp/pdf · 5 MB each)</span>
                            </label>
                            <input id="reply-files" type="file" name="attachments[]" multiple accept=".jpg,.jpeg,.png,.webp,.pdf"
                                class="mt-2 {{ $input }} file:mr-3 file:rounded-lg file:border-0 file:bg-emerald-50 file:px-3 file:py-2 file:font-semibold file:text-emerald-700">
                            @error('attachments') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                            @error('attachments.*') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                        </div>

                        <button type="submit" class="rounded-xl bg-emerald-700 px-6 py-2.5 font-semibold text-white transition hover:bg-emerald-800">
                            Send reply
                        </button>
                    </form>
                @else
                    <div class="rounded-2xl border border-dashed p-5 text-center text-sm text-zinc-500 dark:border-zinc-700">
                        Ye ticket close ho chuka hai. Naya issue ho to <a class="font-semibold text-emerald-700 underline" href="{{ route('growya.support.create') }}">naya ticket banao</a>.
                    </div>
                @endif
            </div>

            {{-- Sidebar --}}
            <aside class="space-y-6">
                <section class="{{ $card }}">
                    <h2 class="mb-3 font-bold">Ticket details</h2>
                    <dl class="space-y-3 text-sm">
                        <div>
                            <dt class="text-xs uppercase tracking-wide text-zinc-400">Created by</dt>
                            <dd>{{ $ticket->creator?->name }}
                                @if ($support && $ticket->creator?->email)
                                    <span class="block text-xs text-zinc-500">{{ $ticket->creator->email }}</span>
                                @endif
                            </dd>
                        </div>
                        @if ($ticket->business)
                            <div>
                                <dt class="text-xs uppercase tracking-wide text-zinc-400">Business</dt>
                                <dd>{{ $ticket->business->name }}</dd>
                            </div>
                        @endif
                        <div>
                            <dt class="text-xs uppercase tracking-wide text-zinc-400">Assigned to</dt>
                            <dd class="{{ $ticket->assignee ? '' : 'text-zinc-400' }}">{{ $ticket->assignee?->name ?? 'Unassigned' }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs uppercase tracking-wide text-zinc-400">Created</dt>
                            <dd>{{ $ticket->created_at?->format('d M Y, h:i A') }}</dd>
                        </div>
                        @if ($ticket->last_reply_at)
                            <div>
                                <dt class="text-xs uppercase tracking-wide text-zinc-400">Last reply</dt>
                                <dd>{{ $ticket->last_reply_at->format('d M Y, h:i A') }}</dd>
                            </div>
                        @endif
                    </dl>
                </section>

                @if ($support)
                    <section class="{{ $card }} space-y-4">
                        <h2 class="font-bold">Support controls</h2>

                        <form method="POST" action="{{ route('growya.support.update', $ticket) }}" class="space-y-3">
                            @csrf @method('PATCH')
                            <label class="block text-sm font-medium">Status
                                <select name="status" class="mt-1 {{ $input }}">
                                    @foreach (['open', 'in_progress', 'resolved', 'closed'] as $v)
                                        <option value="{{ $v }}" @selected($ticket->status === $v)>{{ $label($v) }}</option>
                                    @endforeach
                                </select>
                            </label>
                            <label class="block text-sm font-medium">Priority
                                <select name="priority" class="mt-1 {{ $input }}">
                                    @foreach (['low', 'medium', 'high', 'urgent'] as $v)
                                        <option value="{{ $v }}" @selected($ticket->priority === $v)>{{ ucfirst($v) }}</option>
                                    @endforeach
                                </select>
                            </label>
                            <button type="submit" class="w-full rounded-lg bg-emerald-700 px-4 py-2.5 font-semibold text-white hover:bg-emerald-800">Save changes</button>
                        </form>

                        @if ($admin)
                            <form method="POST" action="{{ route('growya.support.assign', $ticket) }}" class="space-y-3 border-t pt-4 dark:border-zinc-700">
                                @csrf @method('PATCH')
                                <label class="block text-sm font-medium">Assign agent
                                    <select name="assigned_to" class="mt-1 {{ $input }}">
                                        <option value="">Unassigned</option>
                                        @foreach ($agents as $agent)
                                            <option value="{{ $agent->id }}" @selected((int) $ticket->assigned_to === (int) $agent->id)>{{ $agent->name }}</option>
                                        @endforeach
                                    </select>
                                </label>
                                <button type="submit" class="w-full rounded-lg bg-zinc-800 px-4 py-2.5 font-semibold text-white hover:bg-zinc-700">Assign agent</button>
                            </form>
                        @endif
                    </section>

                    <section class="{{ $card }}">
                        <h2 class="mb-4 font-bold">Activity history</h2>

                        <ol class="relative space-y-4 border-l border-zinc-200 pl-4 text-sm dark:border-zinc-700">
                            @forelse ($ticket->events as $e)
                                <li class="relative">
                                    <span class="absolute -left-[1.4rem] top-1.5 h-2.5 w-2.5 rounded-full bg-emerald-600"></span>
                                    <p class="font-medium">{{ $label($e->event) }}
                                        @if ($e->new_value !== null)
                                            <span class="font-normal text-zinc-500">
                                                ({{ $eventValue($e->event, $e->old_value) }} → {{ $eventValue($e->event, $e->new_value) }})
                                            </span>
                                        @endif
                                    </p>
                                    <p class="text-xs text-zinc-400">
                                        {{ $e->actor?->name ?? 'System' }} · {{ $e->created_at?->format('d M Y, H:i') }}
                                    </p>
                                </li>
                            @empty
                                <li class="text-zinc-500">No activity yet.</li>
                            @endforelse
                        </ol>
                    </section>
                @endif
            </aside>
        </div>
    </div>
</x-layouts::app>
