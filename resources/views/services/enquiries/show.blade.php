
<x-layouts::app title="Service Enquiry Details">

@php
    $canEdit = app(
        \App\Services\BusinessAccessService::class
    )->can(auth()->user(), $business, 'services.update');

    $statuses = [
        'new' => 'New',
        'contacted' => 'Contacted',
        'in_progress' => 'In Progress',
        'converted' => 'Converted',
        'closed' => 'Closed',
    ];
@endphp

<div class="mx-auto max-w-4xl space-y-6 p-4 md:p-6">

    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <h1 class="text-2xl font-bold">
                Enquiry #{{ $enquiry->id }}
            </h1>

            <p class="mt-1 text-sm text-zinc-500">
                {{ $business->name }}
            </p>
        </div>

        <a
            href="{{ route('businesses.service-enquiries.index', $business) }}"
            class="rounded-xl border border-zinc-300 px-4 py-2 dark:border-zinc-700"
        >
            Back
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

    <div class="space-y-5 rounded-2xl border border-zinc-200 p-6 dark:border-zinc-700">

        <h2 class="text-lg font-semibold">
            Customer Information
        </h2>

        <div class="grid gap-5 sm:grid-cols-2">

            <div>
                <p class="text-sm text-zinc-500">Customer Name</p>
                <p class="mt-1 font-semibold">
                    {{ $enquiry->customer_name ?: 'N/A' }}
                </p>
            </div>

            <div>
                <p class="text-sm text-zinc-500">Mobile Number</p>

                @if($enquiry->phone)
                    <a
                        href="tel:{{ $enquiry->phone }}"
                        class="mt-1 inline-block font-semibold text-blue-600"
                    >
                        {{ $enquiry->phone }}
                    </a>
                @else
                    <p>N/A</p>
                @endif
            </div>

            <div>
                <p class="text-sm text-zinc-500">Service</p>
                <p class="mt-1 font-semibold">
                    {{ $enquiry->service?->name ?? 'N/A' }}
                </p>
            </div>

            <div>
                <p class="text-sm text-zinc-500">Enquiry Date</p>
                <p class="mt-1">
                    {{ $enquiry->created_at->format('d M Y, h:i A') }}
                </p>
            </div>

            <div>
                <p class="text-sm text-zinc-500">Preferred Date</p>
                <p class="mt-1">
                    {{ $enquiry->preferred_date?->format('d M Y') ?? 'Not specified' }}
                </p>
            </div>

            <div>
                <p class="text-sm text-zinc-500">Preferred Time</p>
                <p class="mt-1">
                    {{ $enquiry->preferred_time
                        ? substr($enquiry->preferred_time, 0, 5)
                        : 'Not specified' }}
                </p>
            </div>

        </div>

        <div class="border-t border-zinc-200 pt-5 dark:border-zinc-700">
            <p class="text-sm text-zinc-500">
                Customer Message
            </p>

            <p class="mt-2 whitespace-pre-line leading-7">
                {{ $enquiry->message ?: 'No message provided.' }}
            </p>
        </div>

        <div class="border-t border-zinc-200 pt-5 dark:border-zinc-700">
            <p class="text-sm text-zinc-500">
                Current Status
            </p>

            <p class="mt-1 font-semibold">
                {{ $statuses[$enquiry->status] ?? ucfirst($enquiry->status) }}
            </p>
        </div>
    </div>

    @if($canEdit)
        <form
            method="POST"
            action="{{ route('businesses.service-enquiries.status', [$business, $enquiry]) }}"
            class="space-y-4 rounded-2xl border border-zinc-200 p-6 dark:border-zinc-700"
        >
            @csrf
            @method('PATCH')

            <h2 class="text-lg font-semibold">
                Update Enquiry Status
            </h2>

            <label class="block space-y-2">
                <span class="text-sm font-medium">
                    Status
                </span>

                <select
                    name="status"
                    required
                    class="w-full rounded-xl border border-zinc-300 bg-white px-4 py-2.5 dark:border-zinc-700 dark:bg-zinc-800"
                >
                    @foreach($statuses as $key => $label)
                        <option
                            value="{{ $key }}"
                            @selected(old('status', $enquiry->status) === $key)
                        >
                            {{ $label }}
                        </option>
                    @endforeach
                </select>
            </label>

            <button
                type="submit"
                class="rounded-xl bg-zinc-950 px-6 py-2.5 font-semibold text-white dark:bg-white dark:text-zinc-950"
            >
                Update Status
            </button>
        </form>
    @endif







{{-- ============================================ --}}
{{-- Service Enquiry Follow-up Notes --}}
{{-- ============================================ --}}

<div class="space-y-5">

    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <h2 class="text-xl font-bold">
                Follow-up Notes & History
            </h2>

            <p class="text-sm text-zinc-500">
                Customer conversation and scheduled follow-ups
            </p>
        </div>

        <a
            href="{{ route('businesses.service-follow-ups.index', $business) }}"
            class="rounded-xl border border-zinc-300 px-4 py-2 text-sm font-semibold dark:border-zinc-700"
            wire:navigate
        >
            All Follow-ups
        </a>
    </div>

    {{-- Add Note --}}
    @if($canEdit)

        <form
            method="POST"
            action="{{ route('businesses.service-enquiries.follow-ups.store', [$business, $enquiry]) }}"
            class="space-y-5 rounded-2xl border border-zinc-200 p-5 dark:border-zinc-700"
        >
            @csrf

            <h3 class="text-lg font-semibold">
                Add New Note
            </h3>

            <label class="block space-y-2">

                <span class="text-sm font-medium">
                    Customer Discussion / Note *
                </span>

                <textarea
                    name="note"
                    rows="4"
                    maxlength="10000"
                    required
                    placeholder="Example: Customer interested in website development. Asked to call tomorrow."
                    class="w-full rounded-xl border border-zinc-300 bg-white px-4 py-3 dark:border-zinc-700 dark:bg-zinc-800"
                >{{ old('note') }}</textarea>

                @error('note')
                    <p class="text-sm text-red-600">
                        {{ $message }}
                    </p>
                @enderror

            </label>

            <label class="block space-y-2">

                <span class="text-sm font-medium">
                    Next Follow-up Date & Time (Optional)
                </span>

                <input
                    type="datetime-local"
                    name="follow_up_at"
                    value="{{ old('follow_up_at') }}"
                    min="{{ now()->format('Y-m-d\TH:i') }}"
                    class="w-full rounded-xl border border-zinc-300 bg-white px-4 py-3 dark:border-zinc-700 dark:bg-zinc-800"
                >

                <p class="text-xs text-zinc-500">
                    Leave empty to save only a conversation note.
                    Select a future date to schedule a follow-up.
                </p>

                @error('follow_up_at')
                    <p class="text-sm text-red-600">
                        {{ $message }}
                    </p>
                @enderror

            </label>

            <button
                type="submit"
                class="rounded-xl bg-zinc-950 px-6 py-2.5 font-semibold text-white dark:bg-white dark:text-zinc-950"
            >
                Save Note / Follow-up
            </button>

        </form>

    @endif

    {{-- Notes History --}}
    <div class="space-y-4 rounded-2xl border border-zinc-200 p-5 dark:border-zinc-700">

        <h3 class="text-lg font-semibold">
            Previous Notes
            ({{ $enquiry->followUps->count() }})
        </h3>

        @forelse($enquiry->followUps as $followUp)

            <div class="rounded-xl border border-zinc-200 p-4 dark:border-zinc-700">

                <div class="mb-3 flex flex-wrap items-start justify-between gap-3">

                    <div>
                        <p class="font-semibold">
                            {{ $followUp->creator?->name ?? 'Former Staff / System' }}
                        </p>

                        <p class="text-xs text-zinc-500">
                            {{ $followUp->created_at->format('d M Y, h:i A') }}
                        </p>
                    </div>

                    @if($followUp->follow_up_at)

                        @if($followUp->completed_at)
                            <span class="rounded-full bg-green-100 px-3 py-1 text-xs font-semibold text-green-700">
                                Completed
                            </span>
                        @elseif($followUp->follow_up_at->isPast())
                            <span class="rounded-full bg-red-100 px-3 py-1 text-xs font-semibold text-red-700">
                                Overdue
                            </span>
                        @else
                            <span class="rounded-full bg-amber-100 px-3 py-1 text-xs font-semibold text-amber-700">
                                Pending
                            </span>
                        @endif

                    @else
                        <span class="rounded-full bg-zinc-100 px-3 py-1 text-xs font-semibold text-zinc-600">
                            Note Only
                        </span>
                    @endif

                </div>

                <p class="whitespace-pre-line text-sm leading-7">
                    {{ $followUp->note }}
                </p>

                @if($followUp->follow_up_at)

                    <div class="mt-4 rounded-lg bg-zinc-50 p-3 text-sm dark:bg-zinc-800">

                        <p class="font-semibold">
                            Scheduled Follow-up:
                            {{ $followUp->follow_up_at->format('d M Y, h:i A') }}
                        </p>

                        @if($followUp->completed_at)

                            <p class="mt-2 text-xs text-green-700 dark:text-green-400">
                                Completed on
                                {{ $followUp->completed_at->format('d M Y, h:i A') }}

                                by
                                {{ $followUp->completedBy?->name ?? 'Former Staff / System' }}
                            </p>

                        @elseif($canEdit)

                            <form
                                method="POST"
                                action="{{ route('businesses.service-enquiries.follow-ups.complete', [$business, $enquiry, $followUp]) }}"
                                class="mt-3"
                            >
                                @csrf
                                @method('PATCH')

                                <button
                                    type="submit"
                                    class="rounded-lg bg-green-600 px-4 py-2 text-xs font-semibold text-white"
                                >
                                    Mark Completed
                                </button>
                            </form>

                        @endif
                    </div>

                @endif

            </div>

        @empty

            <p class="py-6 text-center text-sm text-zinc-500">
                No follow-up notes added yet.
            </p>

        @endforelse

    </div>

</div>



@if($canEdit)

    <a
        href="{{ route('businesses.appointments.create', [
            'business' => $business,
            'enquiry_id' => $enquiry->id,
        ]) }}"
        wire:navigate
        class="inline-flex items-center gap-2 rounded-xl bg-blue-600 px-5 py-2.5 text-sm font-semibold text-white"
    >
       
        Create Appointment
    </a>

@endif

</div>

</x-layouts::app>
