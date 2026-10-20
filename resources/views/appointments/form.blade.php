
<x-layouts::app title="Create Appointment">

@php
    $selectedService = old(
        'service_id',
        $enquiry?->service_id
    );

    $name = old(
        'customer_name',
        $enquiry?->customer_name
    );

    $phone = old(
        'customer_phone',
        $enquiry?->phone
    );
@endphp

<div class="mx-auto max-w-4xl space-y-6 p-4 md:p-6">

    <div class="flex items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold">
                Create Appointment
            </h1>

            <p class="mt-1 text-sm text-zinc-500">
                {{ $business->name }}
            </p>
        </div>

        <a
            href="{{ route('businesses.appointments.index', $business) }}"
            class="rounded-xl border border-zinc-300 px-4 py-2 text-sm dark:border-zinc-700"
        >
            Back
        </a>
    </div>

    @if($errors->any())
        <div class="rounded-xl bg-red-50 p-4 text-red-700">
            <ul class="list-disc pl-5">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @if($enquiry)
        <div class="rounded-xl bg-blue-50 p-4 text-sm text-blue-800 dark:bg-blue-950 dark:text-blue-200">
            Booking from Service Enquiry #{{ $enquiry->id }}
            — {{ $enquiry->customer_name }}
        </div>
    @endif

    <form
        method="POST"
        action="{{ route('businesses.appointments.store', $business) }}"
        class="space-y-6 rounded-2xl border border-zinc-200 p-6 dark:border-zinc-700"
    >
        @csrf

        @if($enquiry)
            <input
                type="hidden"
                name="service_enquiry_id"
                value="{{ $enquiry->id }}"
            >
        @endif

        <div class="grid gap-5 md:grid-cols-2">

            <label class="block space-y-2">
                <span class="text-sm font-medium">
                    Service *
                </span>

                <select
                    name="service_id"
                    required
                    @disabled($enquiry !== null)
                    class="w-full rounded-xl border border-zinc-300 bg-white px-4 py-2.5 dark:border-zinc-700 dark:bg-zinc-800"
                >
                    <option value="">Select Service</option>

                    @foreach($services as $service)
                        <option
                            value="{{ $service->id }}"
                            @selected((string)$selectedService === (string)$service->id)
                        >
                            {{ $service->name }}
                        </option>
                    @endforeach
                </select>

                @if($enquiry)
                    <input
                        type="hidden"
                        name="service_id"
                        value="{{ $enquiry->service_id }}"
                    >
                @endif
            </label>

            <label class="block space-y-2">
                <span class="text-sm font-medium">
                    Customer Name *
                </span>

                <input
                    type="text"
                    name="customer_name"
                    value="{{ $name }}"
                    maxlength="255"
                    required
                    class="w-full rounded-xl border border-zinc-300 bg-white px-4 py-2.5 dark:border-zinc-700 dark:bg-zinc-800"
                >
            </label>

            <label class="block space-y-2">
                <span class="text-sm font-medium">
                    Mobile Number *
                </span>

                <input
                    type="tel"
                    name="customer_phone"
                    value="{{ $phone }}"
                    pattern="[6-9][0-9]{9}"
                    maxlength="10"
                    inputmode="numeric"
                    required
                    class="w-full rounded-xl border border-zinc-300 bg-white px-4 py-2.5 dark:border-zinc-700 dark:bg-zinc-800"
                >
            </label>

            <label class="block space-y-2">
                <span class="text-sm font-medium">
                    Appointment Date *
                </span>

                <input
                    type="date"
                    name="appointment_date"
                    value="{{ old('appointment_date', $enquiry?->preferred_date?->format('Y-m-d')) }}"
                    min="{{ now()->toDateString() }}"
                    required
                    class="w-full rounded-xl border border-zinc-300 bg-white px-4 py-2.5 dark:border-zinc-700 dark:bg-zinc-800"
                >
            </label>

            <label class="block space-y-2">
                <span class="text-sm font-medium">
                    Appointment Time *
                </span>

                <input
                    type="time"
                    name="appointment_time"
                    value="{{ old('appointment_time', $enquiry?->preferred_time ? substr($enquiry->preferred_time, 0, 5) : '') }}"
                    required
                    class="w-full rounded-xl border border-zinc-300 bg-white px-4 py-2.5 dark:border-zinc-700 dark:bg-zinc-800"
                >
            </label>
        </div>

        <label class="block space-y-2">
            <span class="text-sm font-medium">
                Appointment Notes
            </span>

            <textarea
                name="notes"
                rows="4"
                maxlength="5000"
                placeholder="Appointment notes, customer discussion or instructions..."
                class="w-full rounded-xl border border-zinc-300 bg-white px-4 py-3 dark:border-zinc-700 dark:bg-zinc-800"
            >{{ old('notes', $enquiry?->message) }}</textarea>
        </label>

        <button
            type="submit"
            class="rounded-xl bg-zinc-950 px-6 py-3 font-semibold text-white dark:bg-white dark:text-zinc-950"
        >
            Save Appointment
        </button>
    </form>

</div>

</x-layouts::app>
