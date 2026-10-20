
<x-layouts::app title="Appointments">

@php
    $canEdit = app(
        \App\Services\BusinessAccessService::class
    )->can(auth()->user(), $business, 'services.update');

    $statuses = [
        'pending' => 'Pending',
        'confirmed' => 'Confirmed',
        'completed' => 'Completed',
        'cancelled' => 'Cancelled',
    ];
@endphp

<div class="mx-auto max-w-7xl space-y-6 p-4 md:p-6">

    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <h1 class="text-2xl font-bold">
                Appointment Management
            </h1>
            <p class="text-sm text-zinc-500">
                {{ $business->name }}
            </p>
        </div>

        <div class="flex flex-wrap gap-2">
            <a
                href="{{ route('businesses.service-enquiries.index', $business) }}"
                class="rounded-xl border border-zinc-300 px-4 py-2 dark:border-zinc-700"
            >
                Service Enquiries
            </a>

            @if($canEdit)
                <a
                    href="{{ route('businesses.appointments.create', $business) }}"
                    class="rounded-xl bg-zinc-950 px-5 py-2.5 font-semibold text-white dark:bg-white dark:text-zinc-950"
                >
                    + New Appointment
                </a>
            @endif
        </div>
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

    <div class="grid grid-cols-2 gap-3 md:grid-cols-3 xl:grid-cols-6">
        @foreach([
            'total' => 'Total',
            'today' => 'Today Active',
            'upcoming' => 'Upcoming',
            'pending' => 'Pending',
            'completed' => 'Completed',
            'cancelled' => 'Cancelled',
        ] as $key => $label)

            <div class="rounded-xl border border-zinc-200 p-4 dark:border-zinc-700">
                <p class="text-sm text-zinc-500">{{ $label }}</p>
                <p class="mt-2 text-2xl font-bold">
                    {{ $stats[$key] ?? 0 }}
                </p>
            </div>

        @endforeach
    </div>

    <form
        method="GET"
        class="grid gap-3 rounded-2xl border border-zinc-200 p-4 dark:border-zinc-700 md:grid-cols-3 lg:grid-cols-5"
    >
        <input
            name="search"
            type="text"
            value="{{ request('search') }}"
            placeholder="Customer / Mobile"
            class="rounded-xl border border-zinc-300 bg-white px-3 py-2 dark:border-zinc-700 dark:bg-zinc-800"
        >

        <select
            name="service_id"
            class="rounded-xl border border-zinc-300 bg-white px-3 py-2 dark:border-zinc-700 dark:bg-zinc-800"
        >
            <option value="">All Services</option>
            @foreach($services as $service)
                <option
                    value="{{ $service->id }}"
                    @selected((string)request('service_id') === (string)$service->id)
                >
                    {{ $service->name }}
                </option>
            @endforeach
        </select>

        <select
            name="status"
            class="rounded-xl border border-zinc-300 bg-white px-3 py-2 dark:border-zinc-700 dark:bg-zinc-800"
        >
            <option value="">All Status</option>
            @foreach($statuses as $key => $label)
                <option
                    value="{{ $key }}"
                    @selected(request('status') === $key)
                >
                    {{ $label }}
                </option>
            @endforeach
        </select>

        <input
            name="date_from"
            type="date"
            value="{{ request('date_from') }}"
            title="From Date"
            class="rounded-xl border border-zinc-300 bg-white px-3 py-2 dark:border-zinc-700 dark:bg-zinc-800"
        >

        <input
            name="date_to"
            type="date"
            value="{{ request('date_to') }}"
            title="To Date"
            class="rounded-xl border border-zinc-300 bg-white px-3 py-2 dark:border-zinc-700 dark:bg-zinc-800"
        >

        <div class="flex flex-wrap gap-3 md:col-span-3 lg:col-span-5">
            <button
                type="submit"
                class="rounded-xl bg-zinc-950 px-5 py-2 text-white dark:bg-white dark:text-zinc-950"
            >
                Apply Filters
            </button>

            <a
                href="{{ route('businesses.appointments.index', $business) }}"
                class="rounded-xl border border-zinc-300 px-5 py-2 dark:border-zinc-700"
            >
                Reset
            </a>
        </div>
    </form>

    <div class="overflow-x-auto rounded-2xl border border-zinc-200 dark:border-zinc-700">
        <table class="w-full text-left text-sm">
            <thead class="bg-zinc-100 dark:bg-zinc-800">
                <tr>
                    <th class="p-4">Appointment</th>
                    <th class="p-4">Customer</th>
                    <th class="p-4">Service</th>
                    <th class="p-4">Date & Time</th>
                    <th class="p-4">Status</th>
                    <th class="p-4">Action</th>
                </tr>
            </thead>

            <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                @forelse($appointments as $appointment)
                    <tr>
                        <td class="p-4 font-semibold">
                            #{{ $appointment->id }}
                        </td>

                        <td class="p-4">
                            <p class="font-semibold">
                                {{ $appointment->customer_name ?: 'Customer' }}
                            </p>

                            @if($appointment->customer_phone)
                                <a
                                    href="tel:{{ $appointment->customer_phone }}"
                                    class="text-blue-600"
                                >
                                    {{ $appointment->customer_phone }}
                                </a>
                            @endif
                        </td>

                        <td class="p-4">
                            {{ $appointment->service?->name ?? 'N/A' }}
                        </td>

                        <td class="whitespace-nowrap p-4">
                            {{ $appointment->appointment_date->format('d M Y') }}
                            <p class="text-xs text-zinc-500">
                                {{ substr($appointment->appointment_time, 0, 5) }}
                            </p>
                        </td>

                        <td class="p-4">
                            <span class="rounded-full bg-zinc-100 px-3 py-1 text-xs font-semibold text-zinc-800 dark:bg-zinc-800 dark:text-white">
                                {{ $statuses[$appointment->status] ?? ucfirst($appointment->status) }}
                            </span>
                        </td>

                        <td class="p-4">
                            <a
                                href="{{ route('businesses.appointments.show', [$business, $appointment]) }}"
                                wire:navigate
                                class="font-semibold text-blue-600"
                            >
                                View
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td
                            colspan="6"
                            class="p-8 text-center text-zinc-500"
                        >
                            No appointments found.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{ $appointments->links() }}

</div>

</x-layouts::app>
