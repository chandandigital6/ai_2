@php
    $isInbox = $business !== null;

    $followupBadge = [
        'new' => 'bg-blue-50 text-blue-700 dark:bg-blue-950 dark:text-blue-300',
        'contacted' => 'bg-indigo-50 text-indigo-700 dark:bg-indigo-950 dark:text-indigo-300',
        'followup' => 'bg-amber-50 text-amber-700 dark:bg-amber-950 dark:text-amber-300',
        'converted' => 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300',
        'closed' => 'bg-zinc-100 text-zinc-600 dark:bg-zinc-800 dark:text-zinc-300',
    ];
@endphp

<x-layouts::app :title="$heading" :business="$business">
    <div id="main-content" class="mx-auto max-w-5xl space-y-6 p-4 md:p-8">
        <div class="flex flex-wrap items-end justify-between gap-3">
            <div>
                <h1 class="text-2xl font-bold md:text-3xl">{{ $heading }}</h1>
                <p class="text-sm text-zinc-500">
                    {{ $isInbox ? $business->name . ' ke customers ki conversations' : 'Businesses ke saath aapki conversations' }}
                </p>
            </div>
            @unless ($isInbox)
                <a href="{{ route('public.marketplace.index') }}" wire:navigate
                    class="rounded-xl border px-4 py-2 text-sm font-semibold hover:border-emerald-600 hover:text-emerald-700 dark:border-zinc-700">
                    Explore marketplace
                </a>
            @endunless
        </div>

        <form method="GET" class="flex flex-wrap gap-2">
            <input name="search" value="{{ request('search') }}" maxlength="100"
                placeholder="{{ $isInbox ? 'Customer ka naam search karo' : 'Business ka naam search karo' }}"
                aria-label="Search conversations"
                class="min-w-[14rem] flex-1 rounded-lg border p-2.5 dark:border-zinc-700 dark:bg-zinc-900">
            <button type="submit"
                class="rounded-lg bg-emerald-700 px-5 py-2.5 font-semibold text-white hover:bg-emerald-800">Search</button>
            @if (request()->filled('search'))
                <a href="{{ url()->current() }}" class="self-center text-sm underline">Reset</a>
            @endif
        </form>

        <div class="space-y-3">
            @forelse ($threads as $thread)
                @php
                    $latest = $thread->latestMessage;
                    $readAt = $isInbox ? $thread->business_read_at : $thread->customer_read_at;

                    // Latest message dusre paksh ka ho aur read marker ke baad aaya ho to unread.
                    // (Isse har thread ke liye extra query nahi chalti.)
                    $fromOther =
                        $latest &&
                        ($isInbox
                            ? (int) $latest->sender_id === (int) $thread->customer_id
                            : (int) $latest->sender_id !== (int) auth()->id());
                    $hasUnread = $fromOther && (!$readAt || $latest->created_at->gt($readAt));

                    $name = $isInbox ? $thread->customer?->name : $thread->business?->name;
                    $preview = $latest?->body ?: ($latest?->file_path ? '📎 Attachment' : 'Conversation shuru karo');
                    $blocked = $thread->customer_blocked || $thread->business_blocked;
                    $context = ucfirst($thread->context_type) . ($thread->context_id ? ' #' . $thread->context_id : '');
                @endphp

                <a href="{{ route('growya.chat.show', $thread) }}" wire:navigate
                    class="block rounded-2xl border bg-white p-5 shadow-sm transition hover:border-emerald-500 dark:border-zinc-700 dark:bg-zinc-900 {{ $hasUnread ? 'border-emerald-400' : '' }}">
                    <div class="flex items-start gap-4">
                        <span
                            class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-emerald-100 text-sm font-bold text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300">
                            {{ mb_strtoupper(mb_substr($name ?? '?', 0, 2)) }}
                        </span>

                        <div class="min-w-0 flex-1">
                            <div class="flex flex-wrap items-center justify-between gap-2">
                                <strong
                                    class="truncate {{ $hasUnread ? 'text-zinc-950 dark:text-white' : '' }}">{{ $name ?? 'Deleted' }}</strong>
                                <span
                                    class="text-xs text-zinc-400">{{ $thread->last_message_at?->diffForHumans() }}</span>
                            </div>

                            <p
                                class="mt-1 truncate text-sm {{ $hasUnread ? 'font-semibold text-zinc-800 dark:text-zinc-100' : 'text-zinc-500' }}">
                                {{ \Illuminate\Support\Str::limit($preview, 110) }}
                            </p>

                            <div class="mt-3 flex flex-wrap items-center gap-2 text-xs">
                                <span
                                    class="rounded-full bg-zinc-100 px-2.5 py-1 font-semibold uppercase tracking-wide text-emerald-700 dark:bg-zinc-800 dark:text-emerald-400">{{ $context }}</span>

                                @if ($hasUnread)
                                    <span
                                        class="rounded-full bg-red-600 px-2.5 py-1 font-semibold text-white">Unread</span>
                                @endif

                                @if ($blocked)
                                    <span
                                        class="rounded-full bg-amber-50 px-2.5 py-1 font-semibold text-amber-700 dark:bg-amber-950 dark:text-amber-300">Blocked</span>
                                @endif

                                @if ($isInbox && $thread->followup_status)
                                    <span
                                        class="rounded-full px-2.5 py-1 font-semibold {{ $followupBadge[$thread->followup_status] ?? $followupBadge['closed'] }}">
                                        {{ ucfirst($thread->followup_status) }}
                                    </span>
                                @endif
                            </div>
                        </div>
                    </div>
                </a>
            @empty
                <div class="rounded-2xl border border-dashed p-12 text-center dark:border-zinc-700">
                    <p class="font-semibold">No conversations yet.</p>
                    <p class="mt-1 text-sm text-zinc-500">
                        @if (request()->filled('search'))
                            Is naam se koi conversation nahi mili.
                        @elseif ($isInbox)
                            Jab koi customer message bhejega, yahan dikhega.
                        @else
                            Kisi business ke product ya showroom se chat shuru karo.
                        @endif
                    </p>
                </div>
            @endforelse
        </div>

        {{ $threads->links() }}
    </div>
</x-layouts::app>
