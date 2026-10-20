
<x-layouts::app title="Service Follow-ups">

@php
    $canEdit = app(
        \App\Services\BusinessAccessService::class
    )->can(
        auth()->user(),
        $business,
        'services.update'
    );

    $filters = [
        'pending' => 'Pending',
        'overdue' => 'Overdue',
        'completed' => 'Completed',
        'all' => 'All Follow-ups',
    ];
@endphp

<div class="mx-auto max-w-7xl space-y-6 p-4 md:p-6">

    {{-- Header --}}
    <div class="flex flex-wrap items-center justify-between gap-3">

        <div>
            <h1 class="text-2xl font-bold">
                Service Follow-up Management
            </h1>

            <p class="mt-1 text-sm text-zinc-500">
                {{ $business->name }}
            </p>
        </div>

        <a
            href="{{ route('businesses.service-enquiries.index', $business) }}"
            class="rounded-xl border border-zinc-300 px-4 py-2 text-sm dark:border-zinc-700"
            wire:navigate
        >
            Back to Enquiries
        </a>

    </div>

    @if(session('success'))
        <div class="rounded-xl bg-green-50 p-4 text-green-700">
            {{ session('success') }}
        </div>
    @endif

    {{-- Counters --}}
    <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">

        @foreach($filters as $key => $label)

            <a
                href="{{ route('businesses.service-follow-ups.index', [
                    'business' => $business,
                    'filter' => $key
                ]) }}"
                wire:navigate
                class="rounded-2xl border p-5 transition hover:bg-zinc-50 dark:hover:bg-zinc-800
                    {{ $filter === $key
                        ? 'border-blue-500'
                        : 'border-zinc-200 dark:border-zinc-700' }}"
            >

                <p class="text-sm text-zinc-500">
                    {{ $label }}
                </p>

                <p class="mt-2 text-3xl font-bold
                    {{ $key === 'overdue' ? 'text-red-600' : '' }}">
                    {{ $counts[$key] ?? 0 }}
                </p>

            </a>

        @endforeach

    </div>

    {{-- Search --}}
    <form
        method="GET"
        class="flex flex-wrap gap-3 rounded-2xl border border-zinc-200 p-4 dark:border-zinc-700"
    >

        <input
            type="hidden"
            name="filter"
            value="{{ $filter }}"
        >

        <input
            type="text"
            name="search"
            value="{{ request('search') }}"
            placeholder="Search customer name or mobile..."
            class="min-w-60 flex-1 rounded-xl border border-zinc-300 bg-white px-4 py-2.5 dark:border-zinc-700 dark:bg-zinc-800"
        >

        <button
            type="submit"
            class="rounded-xl bg-zinc-950 px-5 py-2.5 text-white dark:bg-white dark:text-zinc-950"
        >
            Search
        </button>

        <a
            href="{{ route('businesses.service-follow-ups.index', [
                'business' => $business,
                'filter' => $filter,
            ]) }}"
            class="rounded-xl border border-zinc-300 px-5 py-2.5 dark:border-zinc-700"
        >
            Reset
        </a>

    </form>

    {{-- Follow-up Table --}}
    <div class="overflow-x-auto rounded-2xl border border-zinc-200 dark:border-zinc-700">

        <table class="w-full text-left text-sm">

            <thead class="bg-zinc-100 dark:bg-zinc-800">

                <tr>
                    <th class="p-4">Customer</th>
                    <th class="p-4">Service</th>
                    <th class="p-4">Follow-up Date</th>
                    <th class="p-4">Note</th>
                    <th class="p-4">Created By</th>
                    <th class="p-4">Status</th>
                    <th class="p-4">Actions</th>
                </tr>

            </thead>

            <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">

                @forelse($followUps as $followUp)

                    @php
                        $isCompleted = $followUp->completed_at !== null;
                        $isOverdue = !$isCompleted
                            && $followUp->follow_up_at->isPast();
                    @endphp

                    <tr>

                        <td class="p-4">
                            <p class="font-semibold">
                                {{ $followUp->enquiry?->customer_name ?: 'Customer' }}
                            </p>

                            @if($followUp->enquiry?->phone)
                                <a
                                    href="tel:{{ $followUp->enquiry->phone }}"
                                    class="mt-1 inline-block text-blue-600"
                                >
                                    {{ $followUp->enquiry->phone }}
                                </a>
                            @endif
                        </td>

                        <td class="p-4">
                            {{ $followUp->enquiry?->service?->name ?? 'N/A' }}
                        </td>

                        <td class="whitespace-nowrap p-4">
                            {{ $followUp->follow_up_at->format('d M Y') }}

                            <span class="block text-xs text-zinc-500">
                                {{ $followUp->follow_up_at->format('h:i A') }}
                            </span>
                        </td>

                        <td class="max-w-xs p-4">
                            <p class="line-clamp-3">
                                {{ $followUp->note }}
                            </p>
                        </td>

                        <td class="p-4">
                            {{ $followUp->creator?->name ?? 'Former Staff / System' }}
                        </td>

                        <td class="p-4">

                            @if($isCompleted)
                                <span class="rounded-full bg-green-100 px-3 py-1 text-xs font-semibold text-green-700">
                                    Completed
                                </span>
                            @elseif($isOverdue)
                                <span class="rounded-full bg-red-100 px-3 py-1 text-xs font-semibold text-red-700">
                                    Overdue
                                </span>
                            @else
                                <span class="rounded-full bg-amber-100 px-3 py-1 text-xs font-semibold text-amber-700">
                                    Pending
                                </span>
                            @endif

                        </td>

                        <td class="p-4">
                            <div class="flex min-w-28 flex-col gap-3">

                                <a
                                    href="{{ route(
                                        'businesses.service-enquiries.show',
                                        [$business, $followUp->enquiry_id ?? $followUp->service_enquiry_id]
                                    ) }}"
                                    class="font-semibold text-blue-600"
                                    wire:navigate
                                >
                                    View Enquiry
                                </a>

                                @if(!$isCompleted && $canEdit)

                                    <form
                                        method="POST"
                                        action="{{ route(
                                            'businesses.service-enquiries.follow-ups.complete',
                                            [
                                                $business,
                                                $followUp->service_enquiry_id,
                                                $followUp
                                            ]
                                        ) }}"
                                    >
                                        @csrf
                                        @method('PATCH')

                                        <button
                                            type="submit"
                                            class="text-left text-xs font-semibold text-green-600"
                                        >
                                            Mark Completed
                                        </button>
                                    </form>

                                @endif

                            </div>
                        </td>

                    </tr>

                @empty

                    <tr>
                        <td colspan="7" class="p-8 text-center text-zinc-500">
                            No follow-ups found.
                        </td>
                    </tr>

                @endforelse

            </tbody>

        </table>
    </div>

    {{ $followUps->links() }}

</div>

</x-layouts::app>
