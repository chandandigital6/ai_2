
<x-layouts::app title="{{ $gallery->exists ? 'Edit Gallery Media' : 'Upload Gallery Media' }}">

@php
    $editing = $gallery->exists;
@endphp

<div class="mx-auto max-w-4xl space-y-6 p-4 md:p-6">

    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <h1 class="text-2xl font-bold">
                {{ $editing ? 'Edit Gallery Media' : 'Upload Gallery Media' }}
            </h1>

            <p class="mt-1 text-sm text-zinc-500">
                {{ $business->name }}
            </p>
        </div>

        <a
            href="{{ route('businesses.gallery.index', $business) }}"
            class="rounded-xl border border-zinc-300 px-4 py-2 dark:border-zinc-700"
            wire:navigate
        >
            Back to Gallery
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

    <form
        method="POST"
        enctype="multipart/form-data"
        action="{{ $editing
            ? route('businesses.gallery.update', [$business, $gallery])
            : route('businesses.gallery.store', $business) }}"
        class="space-y-6 rounded-2xl border border-zinc-200 p-6 dark:border-zinc-700"
    >
        @csrf

        @if($editing)
            @method('PUT')
        @endif

        @if($editing)

            <div class="space-y-3">

                <p class="text-sm font-semibold">
                    Current Media
                </p>

                @if($gallery->is_video)

                    <video
                        controls
                        preload="metadata"
                        class="max-h-80 w-full rounded-xl bg-black"
                    >
                        <source src="{{ $gallery->media_url }}">
                        Your browser does not support video playback.
                    </video>

                @else

                    <img
                        src="{{ $gallery->media_url }}"
                        alt="{{ $gallery->title ?? 'Gallery image' }}"
                        class="max-h-80 rounded-xl object-contain"
                    >

                @endif

                <p class="text-xs text-zinc-500">
                    To replace this file, delete it and upload new media.
                    Editing changes only its details.
                </p>

            </div>

            <div class="space-y-2">
                <p class="text-sm font-medium">
                    Media Category
                </p>

                <p class="rounded-xl bg-zinc-100 px-4 py-3 text-sm dark:bg-zinc-800">
                    {{ $types[$gallery->media_type] ?? ucfirst($gallery->media_type) }}
                </p>
            </div>

        @else

            <label class="block space-y-2">
                <span class="text-sm font-medium">
                    Media Category *
                </span>

                <select
                    name="media_type"
                    required
                    class="w-full rounded-xl border border-zinc-300 bg-white px-4 py-3 dark:border-zinc-700 dark:bg-zinc-800"
                >
                    @foreach($types as $key => $label)
                        <option
                            value="{{ $key }}"
                            @selected(old('media_type', $gallery->media_type) === $key)
                        >
                            {{ $label }}
                        </option>
                    @endforeach
                </select>
            </label>

            <label class="block space-y-2">
                <span class="text-sm font-medium">
                    Upload Photo / Video *
                </span>

                <input
                    type="file"
                    name="file"
                    required
                    accept="image/jpeg,image/png,image/webp,image/gif,video/mp4,video/webm,video/quicktime"
                    class="w-full rounded-xl border border-zinc-300 bg-white px-4 py-3 dark:border-zinc-700 dark:bg-zinc-800"
                >

                <p class="text-xs text-zinc-500">
                    Images: Maximum 10 MB. Videos: Maximum 100 MB.
                    Choose the correct Media Category.
                </p>
            </label>

        @endif

        <label class="block space-y-2">
            <span class="text-sm font-medium">
                Title
            </span>

            <input
                type="text"
                name="title"
                maxlength="255"
                value="{{ old('title', $gallery->title) }}"
                placeholder="Example: Our Showroom Interior"
                class="w-full rounded-xl border border-zinc-300 bg-white px-4 py-3 dark:border-zinc-700 dark:bg-zinc-800"
            >
        </label>

        <label class="block space-y-2">
            <span class="text-sm font-medium">
                Description
            </span>

            <textarea
                name="description"
                rows="4"
                maxlength="5000"
                placeholder="Write a description about this photo or video..."
                class="w-full rounded-xl border border-zinc-300 bg-white px-4 py-3 dark:border-zinc-700 dark:bg-zinc-800"
            >{{ old('description', $gallery->description) }}</textarea>
        </label>

        <div class="grid gap-5 sm:grid-cols-2">

            <label class="block space-y-2">
                <span class="text-sm font-medium">
                    Display Order *
                </span>

                <input
                    type="number"
                    name="sort_order"
                    min="0"
                    required
                    value="{{ old('sort_order', $gallery->sort_order ?? 0) }}"
                    class="w-full rounded-xl border border-zinc-300 bg-white px-4 py-3 dark:border-zinc-700 dark:bg-zinc-800"
                >

                <span class="text-xs text-zinc-500">
                    Lower number displays first.
                </span>
            </label>

            <label class="block space-y-2">
                <span class="text-sm font-medium">
                    Visibility *
                </span>

                <select
                    name="is_active"
                    required
                    class="w-full rounded-xl border border-zinc-300 bg-white px-4 py-3 dark:border-zinc-700 dark:bg-zinc-800"
                >
                    <option
                        value="1"
                        @selected((string)old('is_active', (int)$gallery->is_active) === '1')
                    >
                        Active
                    </option>

                    <option
                        value="0"
                        @selected((string)old('is_active', (int)$gallery->is_active) === '0')
                    >
                        Inactive
                    </option>
                </select>
            </label>

        </div>

        <button
            type="submit"
            class="rounded-xl bg-zinc-950 px-6 py-3 font-semibold text-white dark:bg-white dark:text-zinc-950"
        >
            {{ $editing ? 'Update Gallery' : 'Upload Media' }}
        </button>

    </form>

</div>

</x-layouts::app>
