
<x-layouts::app :title="'Product Gallery - '.$product->name">

<div class="mx-auto max-w-7xl space-y-6 p-4 md:p-6">

    @php
        $canEdit = app(
            \App\Services\BusinessAccessService::class
        )->can(
            auth()->user(),
            $business,
            'products.update'
        );
    @endphp

    {{-- Header --}}
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold">
                Product Image Gallery
            </h1>

            <p class="mt-1 text-sm text-zinc-500">
                {{ $business->name }} / {{ $product->name }}
            </p>
        </div>

        <a
            href="{{ route('businesses.products.show', [$business, $product]) }}"
            class="rounded-xl border border-zinc-300 px-4 py-2 text-sm dark:border-zinc-700"
        >
            Back to Product
        </a>
    </div>

    @if(session('success'))
        <div class="rounded-xl bg-green-50 p-4 text-sm text-green-700 dark:bg-green-950 dark:text-green-200">
            {{ session('success') }}
        </div>
    @endif

    @if($errors->any())
        <div class="rounded-xl bg-red-50 p-4 text-sm text-red-700 dark:bg-red-950 dark:text-red-200">
            <ul class="list-disc pl-5">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- Multiple Image Upload --}}
    @if($canEdit)
        <form
            method="POST"
            enctype="multipart/form-data"
            action="{{ route('businesses.products.gallery.store', [$business, $product]) }}"
            class="space-y-4 rounded-2xl border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-900"
        >
            @csrf

            <div>
                <h2 class="text-lg font-semibold">
                    Upload Product Images
                </h2>

                <p class="mt-1 text-sm text-zinc-500">
                    Upload up to 10 images at once.
                    Maximum 3 MB per image.
                </p>
            </div>

            <input
                id="product-gallery-upload"
                type="file"
                name="images[]"
                accept=".jpg,.jpeg,.png,.webp"
                multiple
                required
                class="block w-full rounded-xl border border-zinc-300 p-3 text-sm dark:border-zinc-700"
            >

            <div
                id="gallery-upload-preview"
                class="grid grid-cols-2 gap-3 sm:grid-cols-4 lg:grid-cols-5"
            ></div>

            <button
                type="submit"
                class="rounded-xl bg-zinc-950 px-6 py-2.5 font-semibold text-white dark:bg-white dark:text-zinc-950"
            >
                Upload Images
            </button>
        </form>
    @endif

    {{-- Gallery Count --}}
    <div class="flex items-center justify-between">
        <h2 class="text-lg font-semibold">
            Product Images
        </h2>

        <span class="text-sm text-zinc-500">
            {{ $images->count() }} Images
        </span>
    </div>

    {{-- Image Listing --}}
    <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">

        @forelse($images as $image)

            <div class="overflow-hidden rounded-2xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900">

                <div class="relative bg-zinc-100 dark:bg-zinc-800">

                    <img
                        src="{{ asset('storage/' . $image->image) }}"
                        alt="{{ $image->alt_text ?: $product->name }}"
                        class="h-56 w-full object-contain"
                        loading="lazy"
                    >

                    @if($product->featured_image === $image->image)
                        <span class="absolute left-3 top-3 rounded-lg bg-green-600 px-3 py-1 text-xs font-semibold text-white">
                            Featured
                        </span>
                    @endif

                </div>

                <div class="space-y-4 p-4">

                    <form
                        method="POST"
                        action="{{ route('businesses.products.gallery.update', [$business, $product, $image]) }}"
                        class="space-y-3"
                    >
                        @csrf
                        @method('PUT')

                        <label class="block space-y-1">
                            <span class="text-xs text-zinc-500">
                                Alt Text
                            </span>

                            <input
                                type="text"
                                name="alt_text"
                                value="{{ $image->alt_text }}"
                                @disabled(!$canEdit)
                                class="w-full rounded-xl border border-zinc-300 bg-white px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-800"
                            >
                        </label>

                        <label class="block space-y-1">
                            <span class="text-xs text-zinc-500">
                                Sort Order
                            </span>

                            <input
                                type="number"
                                name="sort_order"
                                min="0"
                                value="{{ $image->sort_order }}"
                                @disabled(!$canEdit)
                                required
                                class="w-full rounded-xl border border-zinc-300 bg-white px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-800"
                            >
                        </label>

                        @if($canEdit)
                            <button
                                type="submit"
                                class="rounded-xl border border-zinc-300 px-4 py-2 text-sm font-medium dark:border-zinc-700"
                            >
                                Update Details
                            </button>
                        @endif
                    </form>

                    @if($canEdit)
                        <div class="flex flex-wrap gap-2 border-t border-zinc-200 pt-4 dark:border-zinc-700">

                            @if($product->featured_image !== $image->image)
                                <form
                                    method="POST"
                                    action="{{ route('businesses.products.gallery.featured', [$business, $product, $image]) }}"
                                >
                                    @csrf

                                    <button
                                        type="submit"
                                        class="rounded-xl bg-blue-600 px-3 py-2 text-xs font-semibold text-white"
                                    >
                                        Set Featured
                                    </button>
                                </form>
                            @endif

                            <form
                                method="POST"
                                action="{{ route('businesses.products.gallery.destroy', [$business, $product, $image]) }}"
                                onsubmit="return confirm('Delete this product image?')"
                            >
                                @csrf
                                @method('DELETE')

                                <button
                                    type="submit"
                                    class="rounded-xl border border-red-300 px-3 py-2 text-xs font-semibold text-red-600 dark:border-red-900"
                                >
                                    Delete Image
                                </button>
                            </form>

                        </div>
                    @endif

                </div>
            </div>

        @empty
            <div class="col-span-full rounded-2xl border border-dashed border-zinc-300 p-10 text-center text-zinc-500 dark:border-zinc-700">
                No gallery images uploaded yet.
            </div>
        @endforelse

    </div>

</div>

<script>
(function () {
    const input = document.getElementById('product-gallery-upload');
    const preview = document.getElementById('gallery-upload-preview');

    if (!input || !preview || input.dataset.initialized === '1') {
        return;
    }

    input.dataset.initialized = '1';

    let previewUrls = [];

    input.addEventListener('change', function () {
        previewUrls.forEach(url => URL.revokeObjectURL(url));
        previewUrls = [];
        preview.replaceChildren();

        const files = Array.from(input.files || []);

        if (files.length > 10) {
            alert('Maximum 10 images allowed at once.');
            input.value = '';
            return;
        }

        files.forEach(file => {
            const url = URL.createObjectURL(file);
            previewUrls.push(url);

            const image = document.createElement('img');
            image.src = url;
            image.alt = 'Selected image preview';
            image.className =
                'h-28 w-full rounded-xl border border-zinc-200 object-contain p-2 dark:border-zinc-700';

            preview.appendChild(image);
        });
    });
})();
</script>

</x-layouts::app>
