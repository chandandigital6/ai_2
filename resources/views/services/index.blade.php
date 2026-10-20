
<x-layouts::app title="Manage Services">

@php
    $access = app(\App\Services\BusinessAccessService::class);

    $canCreate = $access->can(auth()->user(), $business, 'services.create');
    $canEdit = $access->can(auth()->user(), $business, 'services.update');
    $canDelete = $access->can(auth()->user(), $business, 'services.delete');
@endphp

<div class="mx-auto max-w-7xl space-y-6 p-4 md:p-6">

    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold">
                Services Management
            </h1>

            <p class="text-sm text-zinc-500">
                {{ $business->name }}
            </p>
        </div>

        <div class="flex flex-wrap gap-3">
            <a
                href="{{ route('businesses.service-enquiries.index', $business) }}"
                class="rounded-xl border border-zinc-300 px-4 py-2 text-sm dark:border-zinc-700"
            >
                View Enquiries
            </a>


            <a
    href="{{ route('businesses.appointments.index', $business) }}"
    wire:navigate
    class="inline-flex items-center gap-2 rounded-xl border border-zinc-300 px-5 py-2.5 text-sm font-semibold dark:border-zinc-700"
>
   view
    Appointments
</a>

        </div>

        @if($canCreate)
            <a
                href="{{ route('businesses.services.create', $business) }}"
                class="rounded-xl bg-zinc-950 px-5 py-2.5 text-sm font-semibold text-white dark:bg-white dark:text-zinc-950"
            >
                + Add Service
            </a>
        @endif
    </div>

    @if(session('success'))
        <div class="rounded-xl bg-green-50 p-4 text-green-700">
            {{ session('success') }}
        </div>
    @endif

    <form method="GET" class="flex flex-wrap gap-3">

        <input
            type="text"
            name="search"
            value="{{ request('search') }}"
            placeholder="Search services..."
            class="min-w-60 flex-1 rounded-xl border border-zinc-300 bg-white px-4 py-2.5 dark:border-zinc-700 dark:bg-zinc-800"
        >

        <select
            name="status"
            class="rounded-xl border border-zinc-300 bg-white px-4 py-2.5 dark:border-zinc-700 dark:bg-zinc-800"
        >
            <option value="">All Status</option>

            <option
                value="active"
                @selected(request('status') === 'active')
            >
                Active
            </option>

            <option
                value="inactive"
                @selected(request('status') === 'inactive')
            >
                Inactive
            </option>
        </select>

        <button
            type="submit"
            class="rounded-xl bg-zinc-900 px-5 py-2.5 text-white"
        >
            Search
        </button>

        <a
            href="{{ route('businesses.services.index', $business) }}"
            class="rounded-xl border border-zinc-300 px-5 py-2.5 dark:border-zinc-700"
        >
            Reset
        </a>
    </form>

    <div class="overflow-x-auto rounded-2xl border border-zinc-200 dark:border-zinc-700">
        <table class="w-full text-left text-sm">

            <thead class="bg-zinc-100 dark:bg-zinc-800">
                <tr>
                    <th class="p-4">Service</th>
                    <th class="p-4">Category</th>
                    <th class="p-4">Price</th>
                    <th class="p-4">Duration</th>
                    <th class="p-4">Status</th>
                    <th class="p-4">Actions</th>
                </tr>
            </thead>

            <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">

                @forelse($services as $service)

                    <tr>
                        <td class="p-4">
                            <div class="flex min-w-52 items-center gap-3">

                                @if($service->featured_image)
                                    <img
                                        src="{{ asset('storage/' . $service->featured_image) }}"
                                        alt="{{ $service->name }}"
                                        class="h-14 w-14 rounded-xl object-cover"
                                    >
                                @else
                                    <div class="flex h-14 w-14 items-center justify-center rounded-xl bg-zinc-100 text-xs text-zinc-500">
                                        No Image
                                    </div>
                                @endif

                                <div>
                                    <p class="font-semibold">
                                        {{ $service->name }}
                                    </p>

                                    <p class="mt-1 text-xs text-zinc-500">
                                        {{ $service->images_count }} gallery images
                                    </p>
                                </div>
                            </div>
                        </td>

                        <td class="p-4">
                            {{ $service->category?->name ?? '—' }}
                        </td>

                        <td class="p-4">
                            @if($service->price_type === 'free')
                                Free
                            @elseif($service->price_type === 'on_request')
                                On Request
                            @else
                                @if($service->price_type === 'starting_from')
                                    <span class="text-xs text-zinc-500">From</span>
                                @endif

                                <span class="font-semibold">
                                    ₹{{ number_format((float) ($service->offer_price ?? $service->price), 2) }}
                                </span>
                            @endif
                        </td>

                        <td class="p-4">
                            {{ $service->duration_minutes ? $service->duration_minutes.' min' : '—' }}
                        </td>

                        <td class="p-4">
                            <span class="{{ $service->status === 'active' ? 'text-green-600' : 'text-red-600' }} font-semibold">
                                {{ ucfirst($service->status) }}
                            </span>
                        </td>

                        <td class="p-4">
                            <div class="flex flex-wrap gap-3">
                                <a
                                    href="{{ route('businesses.services.show', [$business, $service]) }}"
                                    class="font-medium text-blue-600"
                                >
                                    View
                                </a>

                                @if($canEdit)
                                    <a
                                        href="{{ route('businesses.services.edit', [$business, $service]) }}"
                                        class="font-medium text-amber-600"
                                    >
                                        Edit
                                    </a>
                                @endif

                                @if($canDelete)
                                    <form
                                        method="POST"
                                        action="{{ route('businesses.services.destroy', [$business, $service]) }}"
                                        onsubmit="return confirm('Delete this service?')"
                                    >
                                        @csrf
                                        @method('DELETE')

                                        <button type="submit" class="font-medium text-red-600">
                                            Delete
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </td>
                    </tr>

                @empty
                    <tr>
                        <td colspan="6" class="p-8 text-center text-zinc-500">
                            No services found.
                        </td>
                    </tr>
                @endforelse

            </tbody>
        </table>
    </div>

    {{ $services->links() }}

</div>

</x-layouts::app>
