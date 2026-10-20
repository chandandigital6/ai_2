<x-layouts::app :title="$service->name">

    @php
        $access = app(\App\Services\BusinessAccessService::class);

        $canEdit = $access->can(auth()->user(), $business, 'services.update');

        $canDelete = $access->can(auth()->user(), $business, 'services.delete');
    @endphp

    <div class="mx-auto max-w-6xl space-y-6 p-4 md:p-6">

        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold">
                    {{ $service->name }}
                </h1>

                <p class="mt-1 text-sm text-zinc-500">
                    {{ $business->name }}
                    /
                    {{ $service->category?->name ?? 'Uncategorized' }}
                </p>
            </div>

            <div class="flex flex-wrap gap-3">
                <a href="{{ route('businesses.services.index', $business) }}"
                    class="rounded-xl border border-zinc-300 px-4 py-2 dark:border-zinc-700">
                    Back
                </a>



                <a href="{{ route('public.services.enquiries.create', [$business, $service]) }}" target="_blank"
                    rel="noopener noreferrer"
                    class="inline-flex items-center rounded-xl bg-zinc-950 px-5 py-2.5 text-sm font-semibold text-white dark:bg-white dark:text-zinc-950">
                    Open Public Enquiry Form
                </a>
                
                @if ($canEdit)
                    <a href="{{ route('businesses.services.edit', [$business, $service]) }}"
                        class="rounded-xl bg-zinc-950 px-4 py-2 text-white dark:bg-white dark:text-zinc-950">
                        Edit Service
                    </a>
                @endif
            </div>
        </div>

        @if (session('success'))
            <div class="rounded-xl bg-green-50 p-4 text-green-700">
                {{ session('success') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="rounded-xl bg-red-50 p-4 text-red-700">
                <ul class="list-disc pl-5">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="grid gap-6 md:grid-cols-2">

            <div class="rounded-2xl border border-zinc-200 p-5 dark:border-zinc-700">

                @if ($service->featured_image)
                    <img src="{{ asset('storage/' . $service->featured_image) }}" alt="{{ $service->name }}"
                        class="h-80 w-full rounded-xl object-contain">
                @else
                    <div
                        class="flex h-80 items-center justify-center rounded-xl bg-zinc-100 text-zinc-500 dark:bg-zinc-800">
                        No Featured Image
                    </div>
                @endif

            </div>

            <div class="space-y-5 rounded-2xl border border-zinc-200 p-5 dark:border-zinc-700">

                <div>
                    <p class="text-sm text-zinc-500">Service Name</p>
                    <p class="text-xl font-bold">{{ $service->name }}</p>
                </div>

                <div>
                    <p class="text-sm text-zinc-500">Price</p>

                    @if ($service->price_type === 'free')
                        <p class="text-2xl font-bold">Free</p>
                    @elseif($service->price_type === 'on_request')
                        <p class="text-2xl font-bold">Price On Request</p>
                    @else
                        <p class="text-2xl font-bold">
                            @if ($service->price_type === 'starting_from')
                                From
                            @endif

                            ₹{{ number_format((float) ($service->offer_price ?? $service->price), 2) }}
                        </p>

                        @if ($service->offer_price !== null)
                            <p class="text-sm text-zinc-500 line-through">
                                ₹{{ number_format((float) $service->price, 2) }}
                            </p>
                        @endif
                    @endif
                </div>

                <div>
                    <p class="text-sm text-zinc-500">Duration</p>
                    <p class="font-medium">
                        {{ $service->duration_minutes ? $service->duration_minutes . ' minutes' : 'Not specified' }}
                    </p>
                </div>

                <div>
                    <p class="text-sm text-zinc-500">Status</p>
                    <p class="font-semibold">
                        {{ ucfirst($service->status) }}
                    </p>
                </div>

                <div>
                    <p class="text-sm text-zinc-500">Description</p>
                    <p class="mt-2 whitespace-pre-line leading-7">
                        {{ $service->description ?: 'No description added.' }}
                    </p>
                </div>
            </div>
        </div>

        {{-- Gallery Upload --}}
        @if ($canEdit)
            <form method="POST" action="{{ route('businesses.services.images.store', [$business, $service]) }}"
                enctype="multipart/form-data"
                class="space-y-4 rounded-2xl border border-zinc-200 p-5 dark:border-zinc-700">
                @csrf

                <h2 class="text-lg font-semibold">
                    Upload Gallery Images
                </h2>

                <p class="text-sm text-zinc-500">
                    Select up to 10 images at a time. Maximum 3 MB each.
                </p>

                <input type="file" name="images[]" accept="image/jpeg,image/png,image/webp" multiple required
                    class="block w-full rounded-xl border border-zinc-300 p-3 dark:border-zinc-700">

                <button type="submit"
                    class="rounded-xl bg-zinc-950 px-5 py-2.5 font-semibold text-white dark:bg-white dark:text-zinc-950">
                    Upload Images
                </button>
            </form>
        @endif

        {{-- Gallery --}}
        <div class="rounded-2xl border border-zinc-200 p-5 dark:border-zinc-700">

            <h2 class="mb-5 text-lg font-semibold">
                Service Gallery
            </h2>

            @if ($service->images->isEmpty())
                <p class="text-sm text-zinc-500">
                    No gallery images uploaded.
                </p>
            @else
                <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">

                    @foreach ($service->images as $image)
                        <div class="space-y-3 rounded-xl border border-zinc-200 p-3 dark:border-zinc-700">

                            <img src="{{ asset('storage/' . $image->image) }}"
                                alt="{{ $image->alt_text ?: $service->name }}"
                                class="h-44 w-full rounded-lg object-cover">

                            @if ($service->featured_image === $image->image)
                                <span
                                    class="inline-block rounded-lg bg-green-100 px-3 py-1 text-xs font-semibold text-green-700">
                                    Featured Image
                                </span>
                            @endif

                            @if ($canEdit)
                                <div class="flex flex-wrap gap-3">

                                    <form method="POST"
                                        action="{{ route('businesses.services.images.featured', [$business, $service, $image]) }}">
                                        @csrf
                                        @method('PATCH')

                                        <button type="submit" class="text-sm font-semibold text-blue-600">
                                            Set Featured
                                        </button>
                                    </form>

                                    <form method="POST"
                                        action="{{ route('businesses.services.images.destroy', [$business, $service, $image]) }}"
                                        onsubmit="return confirm('Delete this gallery image?')">
                                        @csrf
                                        @method('DELETE')

                                        <button type="submit" class="text-sm font-semibold text-red-600">
                                            Delete Image
                                        </button>
                                    </form>
                                </div>
                            @endif
                        </div>
                    @endforeach

                </div>
            @endif

        </div>

        @if ($canDelete)
            <form method="POST" action="{{ route('businesses.services.destroy', [$business, $service]) }}"
                onsubmit="return confirm('Are you sure you want to delete this service?')">
                @csrf
                @method('DELETE')

                <button type="submit" class="rounded-xl border border-red-300 px-5 py-2.5 font-semibold text-red-600">
                    Delete Service
                </button>
            </form>
        @endif

    </div>

</x-layouts::app>
