<x-layouts::app title="Business Gallery">

    @php
        $canEdit = app(\App\Services\BusinessAccessService::class)->can(auth()->user(), $business, 'services.update');

        $selectClass =
            'rounded-xl border border-zinc-300 bg-white px-4 py-2.5 text-sm dark:border-zinc-700 dark:bg-zinc-800';
    @endphp

    <div class="mx-auto max-w-7xl space-y-6 p-4 md:p-6">

        {{-- Header --}}
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h1 class="text-2xl font-bold">Business Gallery</h1>
                <p class="mt-1 text-sm text-zinc-500">{{ $business->name }}</p>
            </div>

            <div class="flex flex-wrap gap-2">
                <a href="{{ route('businesses.index') }}" wire:navigate
                    class="rounded-xl border border-zinc-300 px-4 py-2.5 text-sm dark:border-zinc-700">
                    Back to Businesses
                </a>

                @if ($canEdit)
                    <a href="{{ route('businesses.gallery.create', $business) }}" wire:navigate
                        class="rounded-xl bg-zinc-950 px-5 py-2.5 text-sm font-semibold text-white dark:bg-white dark:text-zinc-950">
                        + Upload Media
                    </a>
                @endif
            </div>
        </div>

        @if (session('success'))
            <div class="rounded-xl bg-green-50 p-4 text-sm text-green-700 dark:bg-green-950 dark:text-green-200">
                {{ session('success') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="rounded-xl bg-red-50 p-4 text-sm text-red-700 dark:bg-red-950 dark:text-red-200">
                {{ $errors->first() }}
            </div>
        @endif

        {{-- Statistics --}}
        <div class="grid grid-cols-2 gap-3 md:grid-cols-3 xl:grid-cols-6">

            <a href="{{ route('businesses.gallery.index', $business) }}"
                class="rounded-xl border border-zinc-200 p-4 hover:bg-zinc-50 dark:border-zinc-700 dark:hover:bg-zinc-800">
                <p class="text-sm text-zinc-500">All Media</p>
                <p class="mt-1 text-2xl font-bold">{{ $total }}</p>
            </a>

            @foreach ($types as $key => $label)
                <a href="{{ route('businesses.gallery.index', ['business' => $business, 'media_type' => $key]) }}"
                    class="rounded-xl border p-4 hover:bg-zinc-50 dark:hover:bg-zinc-800
                    {{ request('media_type') === $key ? 'border-blue-500' : 'border-zinc-200 dark:border-zinc-700' }}">
                    <p class="text-sm text-zinc-500">{{ $label }}</p>
                    <p class="mt-1 text-2xl font-bold">{{ $counts[$key] ?? 0 }}</p>
                </a>
            @endforeach

        </div>

        {{-- Filters --}}
        <form method="GET"
            class="flex flex-wrap items-end gap-3 rounded-xl border border-zinc-200 p-4 dark:border-zinc-700">

            <label class="space-y-2">
                <span class="block text-sm font-medium">Category</span>
                <select name="media_type" class="{{ $selectClass }}">
                    <option value="">All Categories</option>
                    @foreach ($types as $key => $label)
                        <option value="{{ $key }}" @selected(request('media_type') === $key)>
                            {{ $label }}
                        </option>
                    @endforeach
                </select>
            </label>

            <label class="space-y-2">
                <span class="block text-sm font-medium">Status</span>
                <select name="status" class="{{ $selectClass }}">
                    <option value="">All Status</option>
                    <option value="active" @selected(request('status') === 'active')>Active</option>
                    <option value="inactive" @selected(request('status') === 'inactive')>Inactive</option>
                </select>
            </label>

            <button type="submit"
                class="rounded-xl bg-zinc-950 px-5 py-2.5 text-sm text-white dark:bg-white dark:text-zinc-950">
                Apply
            </button>

            <a href="{{ route('businesses.gallery.index', $business) }}"
                class="rounded-xl border border-zinc-300 px-5 py-2.5 text-sm dark:border-zinc-700">
                Reset
            </a>
        </form>

        {{-- Gallery Grid --}}
        <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">

            @forelse($galleries as $gallery)
                <div class="overflow-hidden rounded-2xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900">

                    {{-- Media preview --}}
                    <div class="relative aspect-video bg-zinc-100 dark:bg-zinc-800">

                        @if ($gallery->is_video)
                            <video controls preload="metadata" playsinline class="h-full w-full bg-black object-contain">
                                <source src="{{ $gallery->media_url }}">
                                Aapka browser video support nahi karta.
                            </video>
                        @else
                            <a href="{{ $gallery->media_url }}" target="_blank" rel="noopener noreferrer">
                                <img src="{{ $gallery->media_url }}"
                                    alt="{{ $gallery->title ?? 'Gallery photo' }}" loading="lazy"
                                    class="h-full w-full object-cover">
                            </a>
                        @endif

                        <span
                            class="pointer-events-none absolute left-3 top-3 rounded-lg bg-black/75 px-3 py-1 text-xs font-semibold text-white">
                            {{ $types[$gallery->media_type] ?? ucfirst($gallery->media_type) }}
                        </span>
                    </div>

                    {{-- Details --}}
                    <div class="space-y-3 p-4">

                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <h3 class="truncate font-bold">
                                    {{ $gallery->title ?: 'Untitled Media' }}
                                </h3>
                                <p class="text-xs text-zinc-500">Order: {{ $gallery->sort_order }}</p>
                            </div>

                            @if ($gallery->is_active)
                                <span
                                    class="rounded-full bg-green-100 px-3 py-1 text-xs font-semibold text-green-700 dark:bg-green-950 dark:text-green-300">
                                    Active
                                </span>
                            @else
                                <span
                                    class="rounded-full bg-zinc-200 px-3 py-1 text-xs font-semibold text-zinc-700 dark:bg-zinc-800 dark:text-zinc-300">
                                    Inactive
                                </span>
                            @endif
                        </div>

                        @if ($gallery->description)
                            <p class="line-clamp-3 whitespace-pre-line text-sm text-zinc-600 dark:text-zinc-400">
                                {{ $gallery->description }}
                            </p>
                        @endif

                        <div
                            class="flex flex-wrap items-center justify-between gap-2 border-t border-zinc-200 pt-3 dark:border-zinc-700">

                            <a href="{{ $gallery->media_url }}" target="_blank" rel="noopener noreferrer"
                                class="text-sm font-semibold text-blue-600">
                                Open File
                            </a>

                            @if ($canEdit)
                                <div class="flex items-center gap-3">
                                    <a href="{{ route('businesses.gallery.edit', [$business, $gallery]) }}"
                                        wire:navigate class="text-sm font-semibold text-blue-600">
                                        Edit
                                    </a>

                                    <form method="POST"
                                        action="{{ route('businesses.gallery.destroy', [$business, $gallery]) }}"
                                        onsubmit="return confirm('Are you sure you want to delete this gallery media?')">
                                        @csrf
                                        @method('DELETE')

                                        <button type="submit" class="text-sm font-semibold text-red-600">
                                            Delete
                                        </button>
                                    </form>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

            @empty
                <div
                    class="rounded-2xl border border-dashed border-zinc-300 p-10 text-center sm:col-span-2 lg:col-span-3 dark:border-zinc-700">
                    <p class="font-semibold">No gallery media found.</p>

                    <p class="mt-2 text-sm text-zinc-500">
                        Upload business photos, videos, team images or certificates to get started.
                    </p>

                    @if ($canEdit)
                        <a href="{{ route('businesses.gallery.create', $business) }}" wire:navigate
                            class="mt-4 inline-block rounded-xl bg-zinc-950 px-5 py-2.5 text-sm font-semibold text-white dark:bg-white dark:text-zinc-950">
                            Upload First Media
                        </a>
                    @endif
                </div>
            @endforelse

        </div>

        <div>
            {{ $galleries->withQueryString()->links() }}
        </div>

    </div>

</x-layouts::app>
