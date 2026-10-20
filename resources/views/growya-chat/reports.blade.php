@php
    $tabs = ['' => 'All', 'pending' => 'Pending', 'reviewed' => 'Reviewed', 'dismissed' => 'Dismissed'];
    $current = (string) request('status', '');

    $badge = [
        'pending' => 'bg-amber-50 text-amber-700 dark:bg-amber-950 dark:text-amber-300',
        'reviewed' => 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300',
        'dismissed' => 'bg-zinc-100 text-zinc-600 dark:bg-zinc-800 dark:text-zinc-300',
    ];

    $input = 'w-full rounded-lg border p-2.5 focus:border-emerald-600 focus:outline-none focus:ring-2 focus:ring-emerald-100 dark:border-zinc-700 dark:bg-zinc-900';
@endphp

<x-layouts::app title="Chat moderation">
    <div id="main-content" class="mx-auto max-w-5xl space-y-6 p-4 md:p-8">
        <div>
            <p class="text-sm font-semibold text-emerald-700 dark:text-emerald-400">Administration</p>
            <h1 class="text-2xl font-bold md:text-3xl">Reported conversations</h1>
            <p class="mt-1 text-sm text-zinc-500">Users ki chat reports ko review karo.</p>
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

        <nav class="flex flex-wrap gap-2" aria-label="Filter by status">
            @foreach ($tabs as $key => $text)
                <a href="{{ route('growya.chat.reports', $key === '' ? [] : ['status' => $key]) }}" wire:navigate
                    @if ($current === (string) $key) aria-current="page" @endif
                    class="rounded-full border px-4 py-1.5 text-sm font-semibold transition
                    {{ $current === (string) $key
                        ? 'border-emerald-700 bg-emerald-700 text-white'
                        : 'border-zinc-300 text-zinc-600 hover:border-emerald-600 hover:text-emerald-700 dark:border-zinc-700 dark:text-zinc-300' }}">
                    {{ $text }}
                </a>
            @endforeach
        </nav>

        <div class="space-y-4">
            @forelse ($reports as $report)
                <article class="space-y-4 rounded-2xl border bg-white p-5 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div>
                            <p class="font-bold">{{ $report->thread?->business?->name ?? 'Business removed' }}</p>
                            <p class="text-sm text-zinc-500">
                                Reported by <strong>{{ $report->reporter?->name ?? 'Deleted user' }}</strong>
                                · {{ $report->created_at?->format('d M Y, H:i') }}
                            </p>
                        </div>
                        <span class="rounded-full px-3 py-1 text-xs font-semibold {{ $badge[$report->status] ?? $badge['dismissed'] }}">
                            {{ ucfirst($report->status) }}
                        </span>
                    </div>

                    <p class="whitespace-pre-wrap rounded-lg bg-zinc-50 p-3 text-sm leading-6 dark:bg-zinc-800">{{ $report->reason }}</p>

                    @if ($report->thread)
                        <a class="inline-flex text-sm font-semibold text-emerald-700 underline dark:text-emerald-400"
                            href="{{ route('growya.chat.show', $report->thread) }}">Open conversation →</a>
                    @endif

                    @if ($report->status === 'pending')
                        <form method="POST" action="{{ route('growya.chat.reports.review', $report) }}"
                            class="grid gap-3 border-t pt-4 dark:border-zinc-700 sm:grid-cols-4">
                            @csrf @method('PATCH')

                            <label class="text-sm font-medium">Decision
                                <select name="status" class="mt-1 {{ $input }}">
                                    <option value="reviewed">Reviewed</option>
                                    <option value="dismissed">Dismissed</option>
                                </select>
                            </label>

                            <label class="text-sm font-medium sm:col-span-2">Review note
                                <input name="admin_note" maxlength="2000" placeholder="Optional note" class="mt-1 {{ $input }}">
                            </label>

                            <div class="flex items-end">
                                <button type="submit" class="w-full rounded-lg bg-emerald-700 px-4 py-2.5 font-semibold text-white hover:bg-emerald-800">
                                    Save review
                                </button>
                            </div>
                        </form>
                    @else
                        <div class="border-t pt-3 text-sm text-zinc-500 dark:border-zinc-700">
                            @if ($report->reviewed_at)
                                Reviewed {{ $report->reviewed_at->format('d M Y, H:i') }}.
                            @endif
                            @if ($report->admin_note)
                                <span class="block text-zinc-700 dark:text-zinc-300"><strong>Note:</strong> {{ $report->admin_note }}</span>
                            @endif
                        </div>
                    @endif
                </article>
            @empty
                <div class="rounded-2xl border border-dashed p-12 text-center dark:border-zinc-700">
                    <p class="font-semibold">No reports found.</p>
                    <p class="mt-1 text-sm text-zinc-500">Is filter mein koi report nahi hai.</p>
                </div>
            @endforelse
        </div>

        {{ $reports->links() }}
    </div>
</x-layouts::app>
