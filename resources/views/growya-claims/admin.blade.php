@php
    $tabs = [
        'pending' => 'Pending',
        'under_review' => 'Under review',
        'approved' => 'Approved',
        'rejected' => 'Rejected',
        'all' => 'All',
    ];

    $badge = [
        'pending' => 'bg-amber-50 text-amber-700 dark:bg-amber-950 dark:text-amber-300',
        'under_review' => 'bg-blue-50 text-blue-700 dark:bg-blue-950 dark:text-blue-300',
        'approved' => 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300',
        'rejected' => 'bg-red-50 text-red-700 dark:bg-red-950 dark:text-red-300',
    ];

    $roles = [
        'proprietor' => 'Proprietor',
        'partner' => 'Partner',
        'director' => 'Director',
        'authorized_representative' => 'Authorized Representative',
    ];
@endphp

<x-layouts::app :title="'Business ownership claims'">
    <main id="main-content" class="mx-auto max-w-6xl space-y-6 p-4 md:p-8">
        <div>
            <p class="text-sm font-semibold text-emerald-700 dark:text-emerald-400">Administration</p>
            <h1 class="text-2xl font-bold md:text-3xl">Business Ownership Claims</h1>
            <p class="mt-1 text-sm text-zinc-500">Approve karne par business ki ownership turant transfer ho jati hai.
                Dhyan se verify karo.</p>
        </div>

        @if (session('success'))
            <div class="rounded-xl bg-emerald-50 p-4 text-sm font-semibold text-emerald-800 dark:bg-emerald-950 dark:text-emerald-200"
                role="status">
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
                <a href="{{ route('growya.claims.admin', ['status' => $key]) }}" wire:navigate
                    @if ($status === $key) aria-current="page" @endif
                    class="rounded-full border px-4 py-1.5 text-sm font-semibold transition
                    {{ $status === $key
                        ? 'border-emerald-700 bg-emerald-700 text-white'
                        : 'border-zinc-300 text-zinc-600 hover:border-emerald-600 hover:text-emerald-700 dark:border-zinc-700 dark:text-zinc-300' }}">
                    {{ $text }}
                </a>
            @endforeach
        </nav>

        <div class="overflow-x-auto rounded-2xl border bg-white shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
            <table class="w-full text-left text-sm">
                <thead class="bg-zinc-50 text-xs uppercase tracking-wide text-zinc-500 dark:bg-zinc-800">
                    <tr>
                        <th class="p-3 font-semibold">Claim</th>
                        <th class="p-3 font-semibold">Business</th>
                        <th class="p-3 font-semibold">Claimant</th>
                        <th class="p-3 font-semibold">Role</th>
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
                                {{ $claim->claimant?->name }}
                                <span class="block text-xs text-zinc-500">{{ $claim->claimant?->email }}</span>
                            </td>
                            <td class="whitespace-nowrap p-3">
                                {{ $roles[$claim->claimed_role] ?? ucfirst(str_replace('_', ' ', (string) $claim->claimed_role)) }}
                            </td>
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
                                    {{ in_array($claim->status, ['pending', 'under_review'], true) ? 'Review' : 'View' }}
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="p-10 text-center">
                                <p class="font-semibold">No claims found.</p>
                                <p class="mt-1 text-sm text-zinc-500">Is filter mein koi claim nahi hai.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{ $claims->links() }}
    </main>
</x-layouts::app>
