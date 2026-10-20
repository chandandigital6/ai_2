
<x-layouts::app title="Appointment Details">

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

    $finalized = in_array(
        $appointment->status,
        ['completed', 'cancelled'],
        true
    );
@endphp

<div class="mx-auto max-w-4xl space-y-6 p-4 md:p-6">

    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <h1 class="text-2xl font-bold">
                Appointment #{{ $appointment->id }}
            </h1>

            <p class="mt-1 text-sm text-zinc-500">
                {{ $business->name }}
            </p>
        </div>

        <a
            href="{{ route('businesses.appointments.index', $business) }}"
            wire:navigate
            class="rounded-xl border border-zinc-300 px-4 py-2 dark:border-zinc-700"
        >
            Back to Appointments
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

    <div class="space-y-6 rounded-2xl border border-zinc-200 p-6 dark:border-zinc-700">

        <h2 class="text-lg font-semibold">
            Customer & Booking Information
        </h2>

        <div class="grid gap-5 sm:grid-cols-2">

            <div>
                <p class="text-sm text-zinc-500">Customer Name</p>
                <p class="font-semibold">
                    {{ $appointment->customer_name ?: $appointment->customer?->name ?: 'N/A' }}
                </p>
            </div>

            <div>
                <p class="text-sm text-zinc-500">Mobile Number</p>

                @if($appointment->customer_phone)
                    <a
                        href="tel:{{ $appointment->customer_phone }}"
                        class="font-semibold text-blue-600"
                    >
                        {{ $appointment->customer_phone }}
                    </a>
                @else
                    <p>N/A</p>
                @endif
            </div>

            <div>
                <p class="text-sm text-zinc-500">Service</p>
                <p class="font-semibold">
                    {{ $appointment->service?->name ?? 'N/A' }}
                </p>
            </div>

            <div>
                <p class="text-sm text-zinc-500">Appointment Date</p>
                <p class="font-semibold">
                    {{ $appointment->appointment_date->format('d M Y') }}
                </p>
            </div>

            <div>
                <p class="text-sm text-zinc-500">Appointment Time</p>
                <p class="font-semibold">
                    {{ substr($appointment->appointment_time, 0, 5) }}
                </p>
            </div>

            <div>
                <p class="text-sm text-zinc-500">Status</p>
                <p class="font-semibold">
                    {{ $statuses[$appointment->status] ?? ucfirst($appointment->status) }}
                </p>
            </div>
        </div>

        @if($appointment->enquiry)
            <div class="border-t border-zinc-200 pt-5 dark:border-zinc-700">

                <p class="mb-2 text-sm text-zinc-500">
                    Linked Service Enquiry
                </p>

                <a
                    href="{{ route('businesses.service-enquiries.show', [$business, $appointment->enquiry]) }}"
                    class="font-semibold text-blue-600"
                    wire:navigate
                >
                    View Enquiry #{{ $appointment->enquiry->id }}
                </a>
            </div>
        @endif

        <div class="border-t border-zinc-200 pt-5 dark:border-zinc-700">
            <p class="text-sm text-zinc-500">Appointment Notes</p>

            <p class="mt-2 whitespace-pre-line leading-7">
                {{ $appointment->notes ?: 'No notes added.' }}
            </p>
        </div>

    </div>

    @if($canEdit)
        <form
            method="POST"
            action="{{ route('businesses.appointments.status', [$business, $appointment]) }}"
            class="space-y-4 rounded-2xl border border-zinc-200 p-6 dark:border-zinc-700"
        >
            @csrf
            @method('PATCH')

            <h2 class="text-lg font-semibold">
                Update Appointment Status
            </h2>

            <label class="block space-y-2">
                <span class="text-sm font-medium">Status</span>

                <select
                    name="status"
                    required
                    @disabled($finalized)
                    class="w-full rounded-xl border border-zinc-300 bg-white px-4 py-2.5 dark:border-zinc-700 dark:bg-zinc-800"
                >
                    @foreach($statuses as $key => $label)
                        <option
                            value="{{ $key }}"
                            @selected(old('status', $appointment->status) === $key)
                        >
                            {{ $label }}
                        </option>
                    @endforeach
                </select>
            </label>

            @if($finalized)
                <p class="text-sm text-zinc-500">
                    This appointment is finalized and cannot be reopened.
                </p>
            @else
                <button
                    type="submit"
                    class="rounded-xl bg-zinc-950 px-6 py-2.5 font-semibold text-white dark:bg-white dark:text-zinc-950"
                >
                    Update Status
                </button>
            @endif

        </form>
    @endif

</div>

</x-layouts::app>
