@php
    $badge = [
        'pending' => 'bg-amber-50 text-amber-700 dark:bg-amber-950 dark:text-amber-300',
        'under_review' => 'bg-blue-50 text-blue-700 dark:bg-blue-950 dark:text-blue-300',
        'approved' => 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300',
        'rejected' => 'bg-red-50 text-red-700 dark:bg-red-950 dark:text-red-300',
    ];
@endphp

<x-layouts::app :title="'My business claims'">
    <main id="main-content" class="mx-auto max-w-5xl space-y-6 p-4 md:p-8">
        <div class="flex flex-wrap items-end justify-between gap-3">
            <div>
                <h1 class="text-2xl font-bold md:text-3xl">My Business Claims</h1>
                <p class="text-sm text-zinc-500">Aapke ownership claims aur unka status.</p>
            </div>
            <a href="{{ route('public.marketplace.index') }}"
                class="rounded-xl border px-4 py-2 text-sm font-semibold hover:border-emerald-600 hover:text-emerald-700 dark:border-zinc-700">
                Browse businesses
            </a>
        </div>

        @if (session('success'))
            <div class="rounded-xl bg-emerald-50 p-4 text-sm font-semibold text-emerald-800 dark:bg-emerald-950 dark:text-emerald-200"
                role="status">
                {{ session('success') }}
            </div>
        @endif

        <div class="overflow-x-auto rounded-2xl border bg-white shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
            <table class="w-full text-left text-sm">
                <thead class="bg-zinc-50 text-xs uppercase tracking-wide text-zinc-500 dark:bg-zinc-800">
                    <tr>
                        <th class="p-3 font-semibold">Claim</th>
                        <th class="p-3 font-semibold">Business</th>
                        <th class="p-3 font-semibold">Status</th>
                        <th class="p-3 font-semibold">Submitted</th>
                        <th class="p-3"></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($claims as $claim)
                        <tr class="border-t transition hover:bg-zinc-50 dark:border-zinc-700 dark:hover:bg-zinc-800">
                            <td class="whitespace-nowrap p-3 text-zinc-500">#{{ $claim->id }}</td>
                            <td class="p-3 font-semibold">{{ $claim->business?->name ?? 'Deleted business' }}</td>
                            <td class="p-3">
                                <span
                                    class="whitespace-nowrap rounded-full px-2.5 py-1 text-xs font-semibold {{ $badge[$claim->status] ?? 'bg-zinc-100 text-zinc-600' }}">
                                    {{ ucwords(str_replace('_', ' ', $claim->status)) }}
                                </span>
                            </td>
                            <td class="whitespace-nowrap p-3 text-zinc-500">{{ $claim->created_at?->format('d M Y') }}
                            </td>
                            <td class="p-3 text-right">
                                <a href="{{ route('growya.claims.show', $claim) }}" wire:navigate
                                    class="inline-flex rounded-lg border px-3 py-1.5 text-xs font-semibold text-emerald-700 hover:border-emerald-600 dark:border-zinc-700 dark:text-emerald-400">
                                    View
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="p-10 text-center">
                                <p class="font-semibold">No claims yet.</p>
                                <p class="mt-1 text-sm text-zinc-500">Kisi business ke page par "Claim this business" se
                                    claim shuru kar sakte ho.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{ $claims->links() }}
    </main>
</x-layouts::app>
