@php
    $dot = [
        'message_sent' => 'bg-emerald-500',
        'assigned' => 'bg-blue-500',
        'blocked' => 'bg-red-500',
        'unblocked' => 'bg-zinc-400',
        'reported' => 'bg-amber-500',
        'report_reviewed' => 'bg-amber-500',
        'followup' => 'bg-indigo-500',
    ];
@endphp

<x-layouts::app title="Chat activity" :business="$thread->business">
    <div id="main-content" class="mx-auto max-w-4xl space-y-6 p-4 md:p-8">
        <a class="text-sm font-semibold text-emerald-700 underline dark:text-emerald-400"
            href="{{ route('growya.chat.show', $thread) }}" wire:navigate>← Back to conversation</a>

        <div>
            <h1 class="text-2xl font-bold md:text-3xl">Chat activity history</h1>
            <p class="text-sm text-zinc-500">{{ $thread->business->name }} · Thread #{{ $thread->id }}</p>
        </div>

        <section class="rounded-2xl border bg-white p-5 shadow-sm dark:border-zinc-700 dark:bg-zinc-900 md:p-6">
            <ol class="relative space-y-5 border-l border-zinc-200 pl-5 dark:border-zinc-700">
                @forelse ($logs as $entry)
                    <li class="relative">
                        <span
                            class="absolute -left-[1.65rem] top-1.5 h-3 w-3 rounded-full {{ $dot[$entry->action] ?? 'bg-zinc-400' }}"></span>

                        <div class="flex flex-wrap items-center justify-between gap-2">
                            <strong>{{ ucfirst(str_replace('_', ' ', $entry->action)) }}</strong>
                            <span class="text-xs text-zinc-400">{{ $entry->created_at?->format('d M Y, H:i') }}</span>
                        </div>
                        <p class="text-sm text-zinc-500">{{ $entry->actor?->name ?? 'System' }}</p>
                        @if ($entry->detail)
                            <p class="mt-1 whitespace-pre-wrap text-sm text-zinc-700 dark:text-zinc-300">
                                {{ $entry->detail }}</p>
                        @endif
                    </li>
                @empty
                    <li class="text-sm text-zinc-500">No activity yet.</li>
                @endforelse
            </ol>
        </section>

        {{ $logs->links() }}
    </div>
</x-layouts::app>
