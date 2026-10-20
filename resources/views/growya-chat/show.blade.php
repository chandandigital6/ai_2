@php
    $chatCustomer = (int) $thread->customer_id === (int) auth()->id();
    $chatManager =
        auth()->user()->hasRole('super_admin', 'web') || (int) $thread->business->owner_id === (int) auth()->id();
    $blockedByMe = $chatCustomer ? $thread->customer_blocked : ($chatManager ? $thread->business_blocked : false);
    $isBlocked = $thread->customer_blocked || $thread->business_blocked;
    $canBlock = $chatCustomer || $chatManager;

    $contextLabel = ucfirst($thread->context_type) . ($thread->context_id ? ' #' . $thread->context_id : '');
    $otherName = $chatCustomer ? $thread->business->name : $thread->customer->name;
    $backUrl = $chatCustomer ? route('growya.chat.mine') : route('growya.chat.inbox', $thread->business);

    $onFirstPage = $messages->currentPage() === 1;
    $followups = ['new', 'contacted', 'followup', 'converted', 'closed'];

    $input =
        'w-full rounded-lg border p-2.5 focus:border-emerald-600 focus:outline-none focus:ring-2 focus:ring-emerald-100 dark:border-zinc-700 dark:bg-zinc-900';
    $smallBtn =
        'rounded-lg border px-4 py-2 text-sm font-semibold hover:border-emerald-600 hover:text-emerald-700 dark:border-zinc-700';

    if ($chatManager) {
        $chatStaff = \App\Models\BusinessUser::where('business_id', $thread->business_id)
            ->where('is_active', true)
            ->with('user:id,name')
            ->get()
            ->filter(fn($e) => $e->hasBusinessPermission('chats.view') && $e->hasBusinessPermission('chats.reply'));
    }
@endphp

<x-layouts::app title="Conversation" :business="$thread->business">
    <div id="main-content" class="mx-auto max-w-4xl space-y-5 p-4 md:p-8">

        {{-- Header --}}
        <div class="flex flex-wrap items-start justify-between gap-3">
            <div class="min-w-0">
                <a href="{{ $backUrl }}" wire:navigate
                    class="text-sm font-semibold text-emerald-700 underline dark:text-emerald-400">← Back</a>
                <h1 class="mt-2 truncate text-2xl font-bold">{{ $otherName }}</h1>
                <p class="text-sm text-zinc-500">
                    {{ $chatCustomer ? 'Business' : 'Customer' }} conversation ·
                    <span
                        class="rounded-full bg-emerald-50 px-2 py-0.5 text-xs font-semibold text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300">{{ $contextLabel }}</span>
                    @if (!$chatCustomer)
                        · {{ $thread->business->name }}
                    @endif
                </p>
            </div>
        </div>

        @if (session('success'))
            <div class="rounded-xl bg-emerald-50 p-3 text-sm font-semibold text-emerald-800 dark:bg-emerald-950 dark:text-emerald-200"
                role="status">
                {{ session('success') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="rounded-xl bg-red-50 p-3 text-sm text-red-800 dark:bg-red-950 dark:text-red-200" role="alert">
                {{ $errors->first() }}
            </div>
        @endif

        {{-- Messages --}}
        <section class="overflow-hidden rounded-2xl border bg-white shadow-sm dark:border-zinc-700 dark:bg-zinc-900"
            aria-label="Messages">
            @if ($messages->hasMorePages())
                <div class="border-b bg-zinc-50 p-2 text-center dark:border-zinc-700 dark:bg-zinc-800">
                    <a href="{{ $messages->nextPageUrl() }}"
                        class="text-xs font-semibold text-emerald-700 underline dark:text-emerald-400">↑ Purane messages
                        dekho</a>
                </div>
            @endif

            @unless ($onFirstPage)
                <div
                    class="border-b bg-amber-50 p-2 text-center text-xs text-amber-900 dark:bg-amber-950 dark:text-amber-200">
                    Aap purane messages dekh rahe ho.
                    <a href="{{ $messages->url(1) }}" class="font-semibold underline">Latest par jao</a>
                </div>
            @endunless

            <div id="chat-messages" class="min-h-52 max-h-[60vh] space-y-3 overflow-y-auto p-4" aria-live="polite">
                @forelse ($messages->reverse() as $message)
                    @php $mine = (int) $message->sender_id === (int) auth()->id(); @endphp
                    <div class="flex {{ $mine ? 'justify-end' : 'justify-start' }}">
                        <div
                            class="max-w-[85%] rounded-2xl px-4 py-3 {{ $mine ? 'rounded-br-sm bg-emerald-700 text-white' : 'rounded-bl-sm bg-zinc-100 text-zinc-900 dark:bg-zinc-800 dark:text-white' }}">
                            @if ($message->body)
                                <p class="whitespace-pre-wrap break-words text-sm">{{ $message->body }}</p>
                            @endif
                            @if ($message->file_path)
                                <a class="mt-1 inline-flex max-w-full items-center gap-1 truncate rounded-lg px-2 py-1 text-xs font-semibold underline {{ $mine ? 'bg-emerald-800' : 'bg-white dark:bg-zinc-700' }}"
                                    href="{{ route('growya.chat.file', [$thread, $message]) }}">📎
                                    {{ $message->original_filename ?: 'Attachment' }}</a>
                            @endif
                            <small
                                class="mt-1 block text-[11px] opacity-70">{{ $message->created_at->format('d M, H:i') }}</small>
                        </div>
                    </div>
                @empty
                    <p class="py-12 text-center text-sm text-zinc-500">Abhi koi message nahi. Pehla message bhejo.</p>
                @endforelse
            </div>

            @if ($messages->previousPageUrl())
                <div class="border-t bg-zinc-50 p-2 text-center dark:border-zinc-700 dark:bg-zinc-800">
                    <a href="{{ $messages->previousPageUrl() }}"
                        class="text-xs font-semibold text-emerald-700 underline dark:text-emerald-400">↓ Naye messages
                        dekho</a>
                </div>
            @endif
        </section>

        {{-- Composer --}}
        @if ($isBlocked)
            <div class="rounded-xl bg-amber-50 p-4 text-sm text-amber-900 dark:bg-amber-950 dark:text-amber-200">
                @if ($blockedByMe)
                    Aapne ye conversation block kiya hai, isliye naye messages nahi bhej sakte. Neeche "Conversation
                    tools" se unblock karo.
                @else
                    Dusre paksh ne ye conversation block kiya hai. Naye messages nahi bheje ja sakte.
                @endif
            </div>
        @else
            <form action="{{ route('growya.chat.send', $thread) }}" method="POST" enctype="multipart/form-data"
                id="chat-form"
                class="space-y-3 rounded-2xl border bg-white p-4 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
                @csrf
                <label for="chat-body" class="block text-sm font-semibold">Message</label>
                <textarea id="chat-body" name="body" rows="3" maxlength="5000"
                    placeholder="Message likho... (Ctrl+Enter se bhejo)" class="{{ $input }}">{{ old('body') }}</textarea>

                <div class="flex flex-wrap items-center justify-between gap-3">
                    <label
                        class="inline-flex cursor-pointer items-center gap-2 rounded-lg border px-3 py-2 text-sm font-semibold hover:border-emerald-600 dark:border-zinc-700">
                        📎 Attach file
                        <input id="chat-file" class="sr-only" type="file" name="attachment"
                            accept="image/jpeg,image/png,image/webp,application/pdf">
                    </label>
                    <span id="chat-file-name" class="min-w-0 flex-1 truncate text-xs text-zinc-500">JPG, PNG, WEBP ya
                        PDF · max 5 MB</span>

                    <button type="submit"
                        class="rounded-xl bg-emerald-700 px-6 py-2.5 font-semibold text-white transition hover:bg-emerald-800">
                        Send
                    </button>
                </div>
            </form>
        @endif

        {{-- Tools --}}
        <details class="rounded-2xl border bg-white p-4 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
            <summary class="cursor-pointer font-semibold">Conversation tools</summary>

            <div class="mt-4 space-y-5">
                <div class="flex flex-wrap items-center gap-3">
                    @if ($canBlock)
                        <form method="POST" action="{{ route('growya.chat.block', $thread) }}">
                            @csrf @method('PATCH')
                            <input type="hidden" name="blocked" value="{{ $blockedByMe ? 0 : 1 }}">
                            <button type="submit"
                                class="{{ $smallBtn }} {{ $blockedByMe ? '' : 'text-red-600' }}"
                                @unless ($blockedByMe) onclick="return confirm('Is conversation ko block karna hai?')" @endunless>
                                {{ $blockedByMe ? 'Unblock conversation' : 'Block conversation' }}
                            </button>
                        </form>
                    @endif

                    @if ($chatManager)
                        <a class="text-sm font-semibold text-emerald-700 underline dark:text-emerald-400"
                            href="{{ route('growya.chat.activity', $thread) }}">Activity history</a>
                    @endif
                </div>

                {{-- Report --}}
                <form method="POST" action="{{ route('growya.chat.report', $thread) }}"
                    class="space-y-2 border-t pt-4 dark:border-zinc-700">
                    @csrf
                    <label for="report-reason" class="block text-sm font-semibold">Report this chat</label>
                    <div class="flex flex-wrap gap-2">
                        <input id="report-reason" name="reason" required minlength="10" maxlength="2000"
                            placeholder="Reason (kam se kam 10 characters)" class="{{ $input }} flex-1">
                        <button type="submit" class="{{ $smallBtn }}">Report chat</button>
                    </div>
                    <p class="text-xs text-zinc-400">Report platform support ko jayegi.</p>
                </form>

                @if ($chatManager)
                    {{-- Assign --}}
                    <form method="POST" action="{{ route('growya.chat.assign', $thread) }}"
                        class="space-y-2 border-t pt-4 dark:border-zinc-700">
                        @csrf @method('PATCH')
                        <label for="assigned_to" class="block text-sm font-semibold">Assign to staff</label>
                        <div class="flex flex-wrap gap-2">
                            <select id="assigned_to" name="assigned_to" class="{{ $input }} flex-1">
                                <option value="">Unassigned</option>
                                @foreach ($chatStaff as $entry)
                                    <option value="{{ $entry->user_id }}" @selected($thread->assigned_to == $entry->user_id)>
                                        {{ $entry->user?->name }}</option>
                                @endforeach
                            </select>
                            <button type="submit" class="{{ $smallBtn }}">Assign</button>
                        </div>
                    </form>

                    {{-- Follow-up --}}
                    <form method="POST" action="{{ route('growya.chat.followup', $thread) }}"
                        class="space-y-2 border-t pt-4 dark:border-zinc-700">
                        @csrf @method('PATCH')
                        <p class="text-sm font-semibold">Follow-up</p>
                        <div class="grid gap-2 sm:grid-cols-3">
                            <label class="text-xs font-medium">Status
                                <select name="followup_status" class="mt-1 {{ $input }}">
                                    @foreach ($followups as $state)
                                        <option value="{{ $state }}" @selected($thread->followup_status === $state)>
                                            {{ ucfirst($state) }}</option>
                                    @endforeach
                                </select>
                            </label>
                            <label class="text-xs font-medium">Next follow-up
                                <input type="datetime-local" name="followup_at"
                                    value="{{ $thread->followup_at?->format('Y-m-d\TH:i') }}"
                                    class="mt-1 {{ $input }}">
                            </label>
                            <label class="text-xs font-medium">Note
                                <input name="note" maxlength="2000" placeholder="Follow-up note"
                                    class="mt-1 {{ $input }}">
                            </label>
                        </div>
                        <button type="submit" class="{{ $smallBtn }}">Save follow-up</button>
                    </form>
                @endif
            </div>
        </details>
    </div>

    <script>
        (function() {
            const root = document.getElementById('chat-messages');
            if (!root) return;
            root.scrollTop = root.scrollHeight;

            // Attachment ka naam dikhao + Ctrl/Cmd+Enter se send
            const fileInput = document.getElementById('chat-file');
            const fileName = document.getElementById('chat-file-name');
            if (fileInput && fileName) {
                fileInput.addEventListener('change', function() {
                    fileName.textContent = fileInput.files.length ? fileInput.files[0].name :
                        'JPG, PNG, WEBP ya PDF · max 5 MB';
                });
            }
            const body = document.getElementById('chat-body');
            const form = document.getElementById('chat-form');
            if (body && form) {
                body.addEventListener('keydown', function(e) {
                    if ((e.ctrlKey || e.metaKey) && e.key === 'Enter') form.requestSubmit();
                });
            }

            // Polling sirf latest page par (purane page par lastId galat hota).
            @if ($onFirstPage)
                let lastId = {{ (int) ($messages->max('id') ?? 0) }};
                const endpoint = @json(route('growya.chat.poll', $thread));

                const timer = setInterval(async () => {
                    if (!document.body.contains(root)) {
                        clearInterval(timer);
                        return;
                    }
                    // Tab hidden ho to sirf skip karo, polling band mat karo.
                    if (document.hidden) return;

                    try {
                        const response = await fetch(endpoint + '?after=' + lastId, {
                            headers: {
                                'Accept': 'application/json'
                            }
                        });
                        if (!response.ok) return;
                        const json = await response.json();

                        for (const m of json.messages || []) {
                            lastId = Math.max(lastId, m.id);

                            const row = document.createElement('div');
                            row.className = 'flex ' + (m.mine ? 'justify-end' : 'justify-start');

                            const bubble = document.createElement('div');
                            bubble.className = 'max-w-[85%] rounded-2xl px-4 py-3 ' + (m.mine ?
                                'rounded-br-sm bg-emerald-700 text-white' :
                                'rounded-bl-sm bg-zinc-100 text-zinc-900 dark:bg-zinc-800 dark:text-white'
                                );

                            if (m.body) {
                                const p = document.createElement('p');
                                p.className = 'whitespace-pre-wrap break-words text-sm';
                                p.textContent = m.body;
                                bubble.appendChild(p);
                            }
                            if (m.attachment) {
                                const a = document.createElement('a');
                                a.href = m.attachment;
                                a.textContent = '📎 Attachment';
                                a.className =
                                    'mt-1 inline-flex items-center gap-1 rounded-lg px-2 py-1 text-xs font-semibold underline ' +
                                    (m.mine ? 'bg-emerald-800' : 'bg-white dark:bg-zinc-700');
                                bubble.appendChild(a);
                            }
                            const time = document.createElement('small');
                            time.className = 'mt-1 block text-[11px] opacity-70';
                            time.textContent = m.time;
                            bubble.appendChild(time);

                            row.appendChild(bubble);
                            root.appendChild(row);
                        }
                        if ((json.messages || []).length) root.scrollTop = root.scrollHeight;
                    } catch (e) {}
                }, 12000);
            @endif
        })();
    </script>
</x-layouts::app>
