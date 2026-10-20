<x-layouts::app :title="'Product Variants - ' . $product->name">

    @php
        $canEdit = app(\App\Services\BusinessAccessService::class)->can(auth()->user(), $business, 'products.update');

        $inputClass =
            'w-full rounded-xl border border-zinc-300 bg-white px-3 py-2.5 text-sm text-zinc-900 focus:border-blue-500 focus:outline-none dark:border-zinc-700 dark:bg-zinc-800 dark:text-white';
        $fileClass =
            'block w-full rounded-xl border border-zinc-300 bg-white p-2 text-sm file:mr-3 file:rounded-lg file:border-0 file:bg-zinc-100 file:px-3 file:py-1.5 file:text-sm dark:border-zinc-700 dark:bg-zinc-800 dark:file:bg-zinc-700';
    @endphp

    <div class="mx-auto max-w-6xl space-y-6 p-4 md:p-6">

        {{-- Header --}}
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold">Product Variants</h1>
                <p class="mt-1 text-sm text-zinc-500">
                    {{ $business->name }} / {{ $product->name }}
                </p>
            </div>

            <a href="{{ route('businesses.products.show', [$business, $product]) }}"
                class="rounded-xl border border-zinc-300 px-4 py-2 text-sm hover:bg-zinc-50 dark:border-zinc-700 dark:hover:bg-zinc-800">
                ← Back to Product
            </a>
        </div>

        {{-- Alerts --}}
        @if (session('success'))
            <div class="rounded-xl bg-green-50 p-4 text-sm text-green-700 dark:bg-green-950 dark:text-green-200">
                {{ session('success') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="rounded-xl bg-red-50 p-4 text-sm text-red-700 dark:bg-red-950 dark:text-red-200">
                <ul class="list-disc pl-5">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        {{-- Add Variant --}}
        @if ($canEdit)
            <div class="rounded-2xl border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-900">
                <h2 class="mb-4 text-base font-semibold">Add New Variant</h2>

                <form method="POST" enctype="multipart/form-data"
                    action="{{ route('businesses.products.variants.store', [$business, $product]) }}" class="space-y-5">
                    @csrf

                    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">

                        <label class="block space-y-1">
                            <span class="text-sm font-medium">Variant Name *</span>
                            <input name="name" required maxlength="255" value="{{ old('name') }}"
                                placeholder="Red / M or 22K / 4g" class="{{ $inputClass }}">
                        </label>

                        <label class="block space-y-1">
                            <span class="text-sm font-medium">SKU</span>
                            <input name="sku" value="{{ old('sku') }}" placeholder="TSHIRT-RED-M"
                                class="{{ $inputClass }}">
                        </label>

                        <label class="block space-y-1">
                            <span class="text-sm font-medium">Status *</span>
                            <select name="status" class="{{ $inputClass }}">
                                <option value="active" @selected(old('status', 'active') === 'active')>Active</option>
                                <option value="inactive" @selected(old('status') === 'inactive')>Inactive</option>
                            </select>
                        </label>

                        <label class="block space-y-1">
                            <span class="text-sm font-medium">Regular Price (₹) *</span>
                            <input type="number" name="price" min="0" step="0.01" required
                                value="{{ old('price') }}" class="{{ $inputClass }}">
                        </label>

                        <label class="block space-y-1">
                            <span class="text-sm font-medium">Offer Price (₹)</span>
                            <input type="number" name="offer_price" min="0" step="0.01"
                                value="{{ old('offer_price') }}" class="{{ $inputClass }}">
                        </label>

                        <label class="block space-y-1">
                            <span class="text-sm font-medium">Stock Quantity *</span>
                            <input type="number" name="stock_quantity" min="0" required
                                value="{{ old('stock_quantity', 0) }}" class="{{ $inputClass }}">
                        </label>

                        <label class="block space-y-1 sm:col-span-2 lg:col-span-3">
                            <span class="text-sm font-medium">Variant Image</span>
                            <input type="file" name="image" accept=".jpg,.jpeg,.png,.webp"
                                class="{{ $fileClass }}">
                            <span class="block text-xs text-zinc-500">JPG, PNG, WebP — Max 3 MB</span>
                        </label>
                    </div>

                    <button type="submit"
                        class="rounded-xl bg-zinc-950 px-6 py-2.5 text-sm font-semibold text-white hover:opacity-90 dark:bg-white dark:text-zinc-950">
                        + Add Variant
                    </button>
                </form>
            </div>
        @endif

        {{-- Variants List --}}
        <div>
            <h2 class="mb-4 text-base font-semibold">
                All Variants
                <span
                    class="ml-1 rounded-full bg-zinc-100 px-2.5 py-0.5 text-xs font-medium text-zinc-600 dark:bg-zinc-800 dark:text-zinc-300">
                    {{ $variants->total() }}
                </span>
            </h2>

            <div class="grid gap-5 lg:grid-cols-2">

                @forelse($variants as $variant)
                    @php
                        $hasOffer =
                            $variant->offer_price !== null && (float) $variant->offer_price < (float) $variant->price;
                        $lowStock = $variant->stock_quantity <= 5;
                    @endphp

                    <div class="rounded-2xl border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-900">

                        {{-- Summary --}}
                        <div class="flex items-start gap-4">

                            @if ($variant->image)
                                <img src="{{ asset('storage/' . $variant->image) }}" alt="{{ $variant->name }}"
                                    class="h-20 w-20 shrink-0 rounded-xl border border-zinc-200 bg-white object-contain p-1 dark:border-zinc-700">
                            @else
                                <div
                                    class="flex h-20 w-20 shrink-0 items-center justify-center rounded-xl bg-zinc-100 text-xs text-zinc-500 dark:bg-zinc-800">
                                    No Image
                                </div>
                            @endif



                            @if ($variant->attributeValues->isNotEmpty())
                                <div class="mb-4 flex flex-wrap gap-2">

                                    @foreach ($variant->attributeValues as $value)
                                        <span
                                            class="rounded-full bg-zinc-100 px-3 py-1.5 text-xs font-medium dark:bg-zinc-800">
                                            {{ $value->attribute?->name }}:
                                            {{ $value->value }}
                                        </span>
                                    @endforeach

                                </div>
                            @endif


                            <div class="min-w-0 flex-1">
                                <div class="flex flex-wrap items-center gap-2">
                                    <h3 class="truncate font-semibold">{{ $variant->name }}</h3>
                                    <span
                                        class="rounded-full px-2.5 py-0.5 text-xs font-medium {{ $variant->status === 'active' ? 'bg-green-100 text-green-700 dark:bg-green-950 dark:text-green-300' : 'bg-zinc-100 text-zinc-600 dark:bg-zinc-800 dark:text-zinc-300' }}">
                                        {{ ucfirst($variant->status) }}
                                    </span>
                                </div>

                                <p class="mt-0.5 break-all text-xs text-zinc-500">
                                    SKU: {{ $variant->sku ?: 'N/A' }}
                                </p>

                                <div class="mt-2 flex flex-wrap items-baseline gap-2">
                                    <span class="text-lg font-bold">
                                        ₹{{ number_format((float) ($hasOffer ? $variant->offer_price : $variant->price), 2) }}
                                    </span>
                                    @if ($hasOffer)
                                        <span class="text-sm text-zinc-400 line-through">
                                            ₹{{ number_format((float) $variant->price, 2) }}
                                        </span>
                                    @endif
                                </div>

                                <p class="mt-1 text-sm {{ $lowStock ? 'text-amber-600' : 'text-zinc-500' }}">
                                    Stock: {{ $variant->stock_quantity }}
                                    @if ($lowStock)
                                        <span class="text-xs">(Low)</span>
                                    @endif
                                </p>
                            </div>
                        </div>

                        {{-- Edit / Delete --}}
                        @if ($canEdit)
                            <details class="mt-4 border-t border-zinc-200 pt-4 dark:border-zinc-700">
                                <summary class="cursor-pointer text-sm font-medium text-blue-600 dark:text-blue-400">
                                    Edit Variant
                                </summary>

                                <form method="POST" enctype="multipart/form-data"
                                    action="{{ route('businesses.products.variants.update', [$business, $product, $variant]) }}"
                                    class="mt-4 space-y-4">
                                    @csrf
                                    @method('PUT')

                                    <div class="grid gap-3 sm:grid-cols-2">

                                        <label class="block space-y-1">
                                            <span class="text-xs text-zinc-500">Variant Name</span>
                                            <input name="name" required value="{{ $variant->name }}"
                                                class="{{ $inputClass }}">
                                        </label>

                                        <label class="block space-y-1">
                                            <span class="text-xs text-zinc-500">SKU</span>
                                            <input name="sku" value="{{ $variant->sku }}"
                                                class="{{ $inputClass }}">
                                        </label>

                                        <label class="block space-y-1">
                                            <span class="text-xs text-zinc-500">Price (₹)</span>
                                            <input type="number" name="price" min="0" step="0.01"
                                                required value="{{ $variant->price }}" class="{{ $inputClass }}">
                                        </label>

                                        <label class="block space-y-1">
                                            <span class="text-xs text-zinc-500">Offer Price (₹)</span>
                                            <input type="number" name="offer_price" min="0" step="0.01"
                                                value="{{ $variant->offer_price }}" class="{{ $inputClass }}">
                                        </label>

                                        <label class="block space-y-1">
                                            <span class="text-xs text-zinc-500">Stock Quantity</span>
                                            <input type="number" name="stock_quantity" min="0" required
                                                value="{{ $variant->stock_quantity }}" class="{{ $inputClass }}">
                                        </label>

                                        <label class="block space-y-1">
                                            <span class="text-xs text-zinc-500">Status</span>
                                            <select name="status" class="{{ $inputClass }}">
                                                <option value="active" @selected($variant->status === 'active')>Active</option>
                                                <option value="inactive" @selected($variant->status === 'inactive')>Inactive</option>
                                            </select>
                                        </label>

                                        <label class="block space-y-1 sm:col-span-2">
                                            <span class="text-xs text-zinc-500">Replace Image</span>
                                            <input type="file" name="image" accept=".jpg,.jpeg,.png,.webp"
                                                class="{{ $fileClass }}">
                                        </label>
                                    </div>

                                    <div class="flex flex-wrap items-center gap-3">
                                        <button type="submit"
                                            class="rounded-xl bg-zinc-950 px-5 py-2 text-sm font-semibold text-white hover:opacity-90 dark:bg-white dark:text-zinc-950">
                                            Update Variant
                                        </button>
                                    </div>
                                </form>


                                <form method="POST"
                                    action="{{ route('businesses.products.variants.destroy', [$business, $product, $variant]) }}"
                                    onsubmit="return confirm('Delete this product variant?')" class="mt-3">
                                    @csrf
                                    @method('DELETE')

                                    <button type="submit"
                                        class="rounded-xl border border-red-300 px-4 py-2 text-sm font-medium text-red-600 hover:bg-red-50 dark:border-red-900 dark:hover:bg-red-950">
                                        Delete Variant
                                    </button>


                                </form>



                                <a href="{{ route('businesses.products.variants.attributes.edit', [$business, $product, $variant]) }}"
                                    class="mt-3 inline-flex items-center rounded-xl border border-zinc-300 px-4 py-2 text-sm font-medium hover:bg-zinc-100 dark:border-zinc-700 dark:hover:bg-zinc-800">
                                    Manage Attributes
                                </a>
                            </details>
                        @endif
                    </div>

                @empty
                    <div
                        class="col-span-full rounded-2xl border border-dashed border-zinc-300 p-10 text-center text-zinc-500 dark:border-zinc-700">
                        No variants added yet.
                    </div>
                @endforelse

            </div>

            <div class="mt-6">
                {{ $variants->links() }}
            </div>
        </div>

    </div>

</x-layouts::app>
