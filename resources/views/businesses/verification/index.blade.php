
<x-layouts::app title="Business Verifications">

@php
    $statuses = [
        'pending' => 'Pending',
        'approved' => 'Approved',
        'rejected' => 'Rejected',
        'all' => 'All Businesses',
    ];
@endphp

<div class="mx-auto max-w-7xl space-y-6 p-4 md:p-6">

    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <h1 class="text-2xl font-bold">
                Business Approval Management
            </h1>
            <p class="mt-1 text-sm text-zinc-500">
                Approve, reject and review business registrations.
            </p>
        </div>

        <a
            href="{{ route('businesses.index') }}"
            wire:navigate
            class="rounded-xl border border-zinc-300 px-4 py-2 text-sm dark:border-zinc-700"
        >
            Back to Businesses
        </a>
    </div>

    @if(session('success'))
        <div class="rounded-xl bg-green-50 p-4 text-green-700">
            {{ session('success') }}
        </div>
    @endif

    @if($errors->any())
        <div class="rounded-xl bg-red-50 p-4 text-red-700">
            {{ $errors->first() }}
        </div>
    @endif

    <div class="grid grid-cols-2 gap-3 lg:grid-cols-4">

        @foreach($statuses as $key => $label)
            <a
                href="{{ route('admin.business-verifications.index', [
                    'status' => $key,
                ]) }}"
                wire:navigate
                class="rounded-xl border p-4
                    {{ $filter === $key
                        ? 'border-blue-500'
                        : 'border-zinc-200 dark:border-zinc-700' }}"
            >
                <p class="text-sm text-zinc-500">
                    {{ $label }}
                </p>

                <p class="mt-2 text-3xl font-bold">
                    {{ $key === 'all' ? $total : ($counts[$key] ?? 0) }}
                </p>
            </a>
        @endforeach

    </div>

    <form method="GET"
          class="flex flex-wrap gap-3 rounded-xl border border-zinc-200 p-4 dark:border-zinc-700">

        <input
            type="hidden"
            name="status"
            value="{{ $filter }}"
        >

        <input
            type="text"
            name="search"
            value="{{ request('search') }}"
            placeholder="Search business, slug or phone..."
            class="min-w-60 flex-1 rounded-xl border border-zinc-300 bg-white px-4 py-2.5 dark:border-zinc-700 dark:bg-zinc-800"
        >

        <button
            type="submit"
            class="rounded-xl bg-zinc-950 px-5 py-2.5 text-white dark:bg-white dark:text-zinc-950"
        >
            Search
        </button>

        <a
            href="{{ route('admin.business-verifications.index') }}"
            class="rounded-xl border border-zinc-300 px-5 py-2.5 dark:border-zinc-700"
        >
            Reset
        </a>
    </form>

    <div class="overflow-x-auto rounded-xl border border-zinc-200 dark:border-zinc-700">

        <table class="w-full text-left text-sm">
            <thead class="bg-zinc-100 dark:bg-zinc-800">
                <tr>
                    <th class="p-4">Business</th>
                    <th class="p-4">Owner</th>
                    <th class="p-4">Business Status</th>
                    <th class="p-4">Verification</th>
                    <th class="p-4">Registered</th>
                    <th class="p-4">Actions</th>
                </tr>
            </thead>

            <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                @forelse($businesses as $business)
                    <tr>
                        <td class="p-4">
                            <p class="font-semibold">
                                {{ $business->name }}
                            </p>

                            <p class="text-xs text-zinc-500">
                                {{ $business->slug }}
                            </p>

                            <p class="text-xs text-zinc-500">
                                {{ $business->phone }}
                            </p>
                        </td>

                        <td class="p-4">
                            {{ $business->owner?->name ?? 'N/A' }}
                        </td>

                        <td class="p-4">
                            {{ ucfirst($business->status) }}
                        </td>

                        <td class="p-4">
                            @if($business->verification_status === 'approved')
                                <span class="rounded-full bg-green-100 px-3 py-1 text-xs font-semibold text-green-700">
                                    Approved
                                </span>
                            @elseif($business->verification_status === 'rejected')
                                <span class="rounded-full bg-red-100 px-3 py-1 text-xs font-semibold text-red-700">
                                    Rejected
                                </span>
                            @else
                                <span class="rounded-full bg-amber-100 px-3 py-1 text-xs font-semibold text-amber-700">
                                    Pending
                                </span>
                            @endif
                        </td>

                        <td class="whitespace-nowrap p-4">
                            {{ $business->created_at?->format('d M Y') }}
                        </td>

                        <td class="p-4">
                            <a
                                href="{{ route('admin.business-verifications.show', $business) }}"
                                wire:navigate
                                class="font-semibold text-blue-600"
                            >
                                Review / Actions
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="p-8 text-center text-zinc-500">
                            No businesses found.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>

    </div>

    {{ $businesses->links() }}

</div>

</x-layouts::app>
