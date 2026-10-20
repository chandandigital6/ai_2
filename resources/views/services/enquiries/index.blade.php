<x-layouts::app title="Service Enquiries">

    @php
        $canEdit = app(\App\Services\BusinessAccessService::class)->can(auth()->user(), $business, 'services.update');

        $statuses = [
            'new' => 'New',
            'contacted' => 'Contacted',
            'in_progress' => 'In Progress',
            'converted' => 'Converted',
            'closed' => 'Closed',
        ];
    @endphp

    <div class="mx-auto max-w-7xl space-y-6 p-4 md:p-6">

        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h1 class="text-2xl font-bold">
                    Service Enquiries
                </h1>

                <p class="text-sm text-zinc-500">
                    {{ $business->name }}
                </p>
            </div>


            <a href="{{ route('businesses.service-follow-ups.index', $business) }}" wire:navigate
                class="inline-flex items-center gap-2 rounded-xl border border-zinc-300 px-5 py-2.5 text-sm font-semibold dark:border-zinc-700">
               
                Follow-up Management
            </a>

            <a href="{{ route('businesses.services.index', $business) }}"
                class="rounded-xl border border-zinc-300 px-4 py-2 text-sm dark:border-zinc-700">
                Back to Services
            </a>
        </div>

        @if (session('success'))
            <div class="rounded-xl bg-green-50 p-4 text-green-700">
                {{ session('success') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="rounded-xl bg-red-50 p-4 text-red-700">
                {{ $errors->first() }}
            </div>
        @endif

        {{-- Statistics --}}
        <div class="grid grid-cols-2 gap-3 lg:grid-cols-3 xl:grid-cols-6">

            <div class="rounded-xl border border-zinc-200 p-4 dark:border-zinc-700">
                <p class="text-sm text-zinc-500">Total</p>
                <p class="mt-1 text-2xl font-bold">
                    {{ $total }}
                </p>
            </div>

            @foreach ($statuses as $key => $label)
                <div class="rounded-xl border border-zinc-200 p-4 dark:border-zinc-700">
                    <p class="text-sm text-zinc-500">
                        {{ $label }}
                    </p>

                    <p class="mt-1 text-2xl font-bold">
                        {{ $counts[$key] ?? 0 }}
                    </p>
                </div>
            @endforeach
        </div>

        {{-- Filters --}}
        <form method="GET"
            class="grid gap-3 rounded-xl border border-zinc-200 p-4 dark:border-zinc-700 md:grid-cols-5">

            <input type="text" name="search" value="{{ request('search') }}" placeholder="Name / Phone / Message"
                class="rounded-xl border border-zinc-300 bg-white px-3 py-2 dark:border-zinc-700 dark:bg-zinc-800">

            <select name="service_id"
                class="rounded-xl border border-zinc-300 bg-white px-3 py-2 dark:border-zinc-700 dark:bg-zinc-800">
                <option value="">All Services</option>

                @foreach ($services as $service)
                    <option value="{{ $service->id }}" @selected((string) request('service_id') === (string) $service->id)>
                        {{ $service->name }}
                    </option>
                @endforeach
            </select>

            <select name="status"
                class="rounded-xl border border-zinc-300 bg-white px-3 py-2 dark:border-zinc-700 dark:bg-zinc-800">
                <option value="">All Statuses</option>

                @foreach ($statuses as $key => $label)
                    <option value="{{ $key }}" @selected(request('status') === $key)>
                        {{ $label }}
                    </option>
                @endforeach
            </select>

            <input type="date" name="date_from" value="{{ request('date_from') }}" title="From Date"
                class="rounded-xl border border-zinc-300 bg-white px-3 py-2 dark:border-zinc-700 dark:bg-zinc-800">

            <input type="date" name="date_to" value="{{ request('date_to') }}" title="To Date"
                class="rounded-xl border border-zinc-300 bg-white px-3 py-2 dark:border-zinc-700 dark:bg-zinc-800">

            <div class="flex gap-3 md:col-span-5">
                <button type="submit"
                    class="rounded-xl bg-zinc-950 px-5 py-2.5 text-white dark:bg-white dark:text-zinc-950">
                    Apply Filters
                </button>

                <a href="{{ route('businesses.service-enquiries.index', $business) }}"
                    class="rounded-xl border border-zinc-300 px-5 py-2.5 dark:border-zinc-700">
                    Reset
                </a>
            </div>
        </form>

        {{-- Enquiries Table --}}
        <div class="overflow-x-auto rounded-xl border border-zinc-200 dark:border-zinc-700">

            <table class="w-full text-left text-sm">

                <thead class="bg-zinc-100 dark:bg-zinc-800">
                    <tr>
                        <th class="p-4">Date</th>
                        <th class="p-4">Customer</th>
                        <th class="p-4">Service</th>
                        <th class="p-4">Preferred Slot</th>
                        <th class="p-4">Status</th>
                        <th class="p-4">Action</th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">

                    @forelse($enquiries as $enquiry)
                        <tr>
                            <td class="whitespace-nowrap p-4">
                                {{ $enquiry->created_at->format('d M Y') }}
                            </td>

                            <td class="p-4">
                                <p class="font-semibold">
                                    {{ $enquiry->customer_name ?: 'Customer' }}
                                </p>

                                @if ($enquiry->phone)
                                    <a href="tel:{{ $enquiry->phone }}" class="text-blue-600">
                                        {{ $enquiry->phone }}
                                    </a>
                                @endif
                            </td>

                            <td class="p-4">
                                {{ $enquiry->service?->name ?? 'Deleted Service' }}
                            </td>

                            <td class="p-4">
                                {{ $enquiry->preferred_date?->format('d M Y') ?? '—' }}

                                @if ($enquiry->preferred_time)
                                    <span class="block">
                                        {{ substr($enquiry->preferred_time, 0, 5) }}
                                    </span>
                                @endif
                            </td>

                            <td class="p-4">
                                {{ $statuses[$enquiry->status] ?? ucfirst($enquiry->status) }}
                            </td>

                            <td class="p-4">
                                <a href="{{ route('businesses.service-enquiries.show', [$business, $enquiry]) }}"
                                    class="font-semibold text-blue-600" wire:navigate>
                                    View
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="p-8 text-center text-zinc-500">
                                No service enquiries found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>

            </table>
        </div>

        {{ $enquiries->links() }}

    </div>

</x-layouts::app>
