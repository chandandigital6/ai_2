@php
    $statuses = ['open', 'in_progress', 'resolved', 'closed'];

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
    $headings = ['Ticket', 'Category', 'Priority', 'Status'];
    if ($support) {
        $headings = array_merge($headings, ['Customer', 'Assigned']);
    }
    $headings[] = 'Updated';
@endphp

<x-layouts::app title="Support Tickets">
    <div id="main-content" class="mx-auto max-w-6xl space-y-6 p-4 md:p-8">
        <div class="flex flex-wrap items-end justify-between gap-3">
            <div>
                <h1 class="text-2xl font-bold md:text-3xl">Support tickets</h1>
                <p class="text-sm text-zinc-500">
                    {{ $admin ? 'Platform ke saare tickets' : ($support ? 'Aapke aur aapko assign kiye gaye tickets' : 'Aapki requests aur support conversations') }}
                </p>
            </div>
            <a href="{{ route('growya.support.create') }}" wire:navigate
                class="rounded-xl bg-emerald-700 px-5 py-2.5 font-semibold text-white transition hover:bg-emerald-800">
                + New ticket
            </a>
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

        {{-- Filters --}}
        <form method="GET" class="flex flex-wrap items-center gap-3">
            <input name="search" value="{{ request('search') }}" placeholder="Ticket number or subject"
                aria-label="Search tickets" maxlength="100"
                class="min-w-[16rem] flex-1 rounded-lg border p-2.5 dark:border-zinc-700 dark:bg-zinc-900">

            <select name="status" aria-label="Filter by status"
                class="rounded-lg border p-2.5 dark:border-zinc-700 dark:bg-zinc-900">
                <option value="">All statuses</option>
                @foreach ($statuses as $s)
                    <option value="{{ $s }}" @selected(request('status') === $s)>{{ $label($s) }}</option>
                @endforeach
            </select>

            <button type="submit" class="rounded-lg bg-emerald-700 px-5 py-2.5 font-semibold text-white hover:bg-emerald-800">Filter</button>

            @if (request()->filled('search') || request()->filled('status'))
                <a href="{{ route('growya.support.index') }}" class="text-sm underline">Reset</a>
            @endif
        </form>

        {{-- Tickets --}}
        <div class="overflow-x-auto rounded-2xl border bg-white shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
            <table class="w-full text-left text-sm">
                <thead class="bg-zinc-50 text-xs uppercase tracking-wide text-zinc-500 dark:bg-zinc-800">
                    <tr>
                        @foreach ($headings as $h)
                            <th class="whitespace-nowrap p-3 font-semibold">{{ $h }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @forelse ($tickets as $t)
                        <tr class="border-t transition hover:bg-zinc-50 dark:border-zinc-700 dark:hover:bg-zinc-800">
                            <td class="max-w-xs p-3">
                                <a class="font-semibold text-emerald-700 underline dark:text-emerald-400"
                                    href="{{ route('growya.support.show', $t) }}">{{ $t->ticket_number }}</a>
                                <p class="mt-0.5 truncate text-zinc-600 dark:text-zinc-300">{{ $t->subject }}</p>
                                @if ($t->business)
                                    <p class="text-xs text-zinc-400">{{ $t->business->name }}</p>
                                @endif
                            </td>
                            <td class="whitespace-nowrap p-3">{{ $label($t->category) }}</td>
                            <td class="p-3">
                                <span class="rounded-full px-2.5 py-1 text-xs font-semibold {{ $priorityBadge[$t->priority] ?? $priorityBadge['low'] }}">
                                    {{ $label($t->priority) }}
                                </span>
                            </td>
                            <td class="p-3">
                                <span class="whitespace-nowrap rounded-full px-2.5 py-1 text-xs font-semibold {{ $statusBadge[$t->status] ?? $statusBadge['closed'] }}">
                                    {{ $label($t->status) }}
                                </span>
                            </td>
                            @if ($support)
                                <td class="whitespace-nowrap p-3">{{ $t->creator?->name }}</td>
                                <td class="whitespace-nowrap p-3 {{ $t->assignee ? '' : 'text-zinc-400' }}">{{ $t->assignee?->name ?? 'Unassigned' }}</td>
                            @endif
                            <td class="whitespace-nowrap p-3 text-zinc-500">{{ $t->updated_at?->format('d M Y') }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ count($headings) }}" class="p-10 text-center">
                                <p class="font-semibold">No support tickets found.</p>
                                @if (request()->filled('search') || request()->filled('status'))
                                    <p class="mt-1 text-sm text-zinc-500">Filter badal kar dobara try karo.</p>
                                @else
                                    <p class="mt-1 text-sm text-zinc-500">Koi dikkat ho to naya ticket banao.</p>
                                @endif
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{ $tickets->links() }}
    </div>
</x-layouts::app>
