<x-layouts::app :title="__('Business Ownership')">
    <div class="mx-auto max-w-6xl space-y-6 p-4 sm:p-7">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <p class="text-sm font-semibold text-emerald-500">SUPER ADMIN</p>
                <h1 class="text-2xl font-bold text-zinc-900 dark:text-white">Business ownership</h1>
                <p class="text-sm text-zinc-500">Assign or transfer a business to a verified active account.</p>
            </div>
            <a href="{{ route('dashboard') }}"
                class="rounded-xl border px-4 py-2 text-sm dark:border-zinc-700">Dashboard</a>
        </div>
        <form method="GET" class="flex gap-2">
            <input name="search" value="{{ $search }}" placeholder="Search business or owner"
                class="min-w-0 flex-1 rounded-xl border border-zinc-300 bg-white px-4 py-3 text-zinc-900 dark:border-zinc-700 dark:bg-zinc-900 dark:text-white" />
            <button class="rounded-xl bg-emerald-600 px-5 py-3 text-sm font-semibold text-white">Search</button>
        </form>
        <div class="overflow-hidden rounded-2xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900">
            @forelse($businesses as $business)
                <div
                    class="flex flex-wrap items-center justify-between gap-3 border-b border-zinc-100 p-4 last:border-0 dark:border-zinc-800">
                    <div>
                        <div class="font-semibold text-zinc-900 dark:text-white">{{ $business->name }} <span
                                class="text-xs font-normal text-zinc-500">#{{ $business->id }}</span></div>
                        <p class="text-sm text-zinc-500">Current owner: {{ $business->owner?->name ?? 'Not assigned' }}
                            (ID {{ $business->owner_id ?? '—' }})</p>
                    </div>
                    <a href="{{ route('growya.ownership.edit', $business) }}"
                        class="rounded-xl bg-emerald-600 px-4 py-2 text-sm font-semibold text-white">Manage
                        ownership</a>
                </div>
            @empty
                <p class="p-8 text-center text-zinc-500">No businesses found.</p>
            @endforelse
        </div>
        {{ $businesses->links() }}
    </div>
</x-layouts::app>
