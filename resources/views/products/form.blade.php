<x-layouts::app :title="$product->exists ? 'Edit Product' : 'Add Product'">

    @php
        $inputClass =
            'w-full rounded-xl border border-zinc-300 bg-white px-3 py-2.5 dark:border-zinc-700 dark:bg-zinc-800 disabled:cursor-not-allowed disabled:opacity-60';
    @endphp

    <div class="mx-auto max-w-5xl space-y-6 p-4 md:p-6">

        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h1 class="text-2xl font-bold">
                    {{ $product->exists ? 'Edit Product' : 'Add Product' }}
                </h1>
                <p class="text-sm text-zinc-500">{{ $business->name }}</p>
            </div>

            <a href="{{ route('businesses.products.index', $business) }}"
                class="rounded-xl border border-zinc-300 px-4 py-2 dark:border-zinc-700">
                Back
            </a>
        </div>

        @if ($errors->any())
            <div class="rounded-xl bg-red-50 p-4 text-sm text-red-700 dark:bg-red-950 dark:text-red-200">
                <ul class="list-disc pl-5">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" enctype="multipart/form-data"
            action="{{ $product->exists
                ? route('businesses.products.update', [$business, $product])
                : route('businesses.products.store', $business) }}"
            class="space-y-6 rounded-2xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
            @csrf

            @if ($product->exists)
                @method('PUT')
            @endif

            <div class="grid gap-5 md:grid-cols-2">

                <label class="space-y-1">
                    <span class="text-sm font-medium">Product Name *</span>
                    <input name="name" required value="{{ old('name', $product->name) }}"
                        class="{{ $inputClass }}">
                </label>

                <label class="space-y-1">
                    <span class="text-sm font-medium">SKU</span>
                    <input name="sku" value="{{ old('sku', $product->sku) }}" placeholder="Example: PRD-001"
                        class="{{ $inputClass }}">
                </label>

                <label class="space-y-1">
                    <span class="text-sm font-medium">Product Category</span>
                    <select name="category_id" class="{{ $inputClass }}">
                        <option value="">Select Category</option>

                        @foreach ($categories as $category)
                            <option value="{{ $category->id }}" @selected((string) old('category_id', $product->category_id) === (string) $category->id)>
                                {{ $category->name }}
                            </option>
                        @endforeach
                    </select>
                </label>

                <label class="space-y-1">
                    <span class="text-sm font-medium">Status *</span>
                    <select name="status" required class="{{ $inputClass }}">
                        @foreach (['active' => 'Active', 'inactive' => 'Inactive'] as $key => $label)
                            <option value="{{ $key }}" @selected(old('status', $product->status ?? 'active') === $key)>
                                {{ $label }}
                            </option>
                        @endforeach
                    </select>
                </label>

                <label class="space-y-1">
                    <span class="text-sm font-medium">Regular Price (₹) *</span>
                    <input type="number" step="0.01" min="0" name="price" required
                        value="{{ old('price', $product->price ?? '0.00') }}" class="{{ $inputClass }}">
                </label>

                <label class="space-y-1">
                    <span class="text-sm font-medium">Offer Price (₹)</span>
                    <input type="number" step="0.01" min="0" name="offer_price"
                        value="{{ old('offer_price', $product->offer_price) }}" class="{{ $inputClass }}">
                </label>

                {{-- Stock: sirf create pe editable --}}
                <label class="space-y-1">
                    <span class="text-sm font-medium">
                        Stock Status {{ $product->exists ? '' : '*' }}
                    </span>
                    <select name="stock_status" @required(!$product->exists) @disabled($product->exists)
                        class="{{ $inputClass }}">
                        <option value="in_stock" @selected(old('stock_status', $product->stock_status ?? 'in_stock') === 'in_stock')>
                            In Stock
                        </option>
                        <option value="out_of_stock" @selected(old('stock_status', $product->stock_status) === 'out_of_stock')>
                            Out of Stock
                        </option>
                    </select>
                </label>

                <label class="space-y-1">
                    <span class="text-sm font-medium">
                        Stock Quantity {{ $product->exists ? '' : '*' }}
                    </span>
                    <input type="number" min="0" name="stock_quantity" @required(!$product->exists)
                        @disabled($product->exists) value="{{ old('stock_quantity', $product->stock_quantity ?? 1) }}"
                        class="{{ $inputClass }}">
                    @if ($product->exists)
                        <span class="block text-xs text-zinc-500">
                            Stock edit yahan se nahi hota. Variants se manage karo.
                        </span>
                    @endif
                </label>

                <label class="space-y-2">
                    <span class="block text-sm font-medium">Featured Image</span>

                    @if ($product->featured_image)
                        <img src="{{ asset('storage/' . $product->featured_image) }}"
                            class="h-32 w-32 rounded-xl object-cover" alt="Product Image">
                    @endif

                    <input type="file" name="featured_image" accept=".jpg,.jpeg,.png,.webp"
                        class="block w-full rounded-xl border border-zinc-300 p-3 text-sm dark:border-zinc-700">
                    <span class="block text-xs text-zinc-500">Maximum 3 MB</span>
                </label>

                <label class="space-y-1">
                    <span class="text-sm font-medium">Product Video URL</span>
                    <input type="url" name="video" value="{{ old('video', $product->video) }}"
                        placeholder="https://..." class="{{ $inputClass }}">
                </label>

            </div>

            <label class="block space-y-1">
                <span class="text-sm font-medium">Product Description</span>
                <textarea name="description" rows="5" class="{{ $inputClass }}">{{ old('description', $product->description) }}</textarea>
            </label>

            {{-- Featured & Trending --}}
            <div class="rounded-2xl border border-zinc-200 p-5 dark:border-zinc-700">

                <h2 class="mb-4 text-lg font-semibold">
                    Product Visibility & Marketing
                </h2>

                <div class="grid gap-4 sm:grid-cols-2">

                    <label
                        class="flex cursor-pointer items-start gap-3 rounded-xl border border-zinc-200 p-4 dark:border-zinc-700">
                        <input type="hidden" name="is_featured" value="0">
                        <input type="checkbox" name="is_featured" value="1" @checked(old('is_featured', $product->is_featured ?? false))
                            class="mt-1 h-4 w-4 rounded">
                        <span>
                            <span class="block font-semibold">Featured Product</span>
                            <span class="mt-1 block text-xs text-zinc-500">
                                Highlight this product in featured sections.
                            </span>
                        </span>
                    </label>

                    <label
                        class="flex cursor-pointer items-start gap-3 rounded-xl border border-zinc-200 p-4 dark:border-zinc-700">
                        <input type="hidden" name="is_trending" value="0">
                        <input type="checkbox" name="is_trending" value="1" @checked(old('is_trending', $product->is_trending ?? false))
                            class="mt-1 h-4 w-4 rounded">
                        <span>
                            <span class="block font-semibold">Trending Product</span>
                            <span class="mt-1 block text-xs text-zinc-500">
                                Mark this product as trending.
                            </span>
                        </span>
                    </label>

                </div>
            </div>

            {{-- SEO Settings --}}
            <div class="rounded-2xl border border-zinc-200 p-5 dark:border-zinc-700">

                <h2 class="mb-4 text-lg font-semibold">Product SEO Settings</h2>

                <div class="space-y-5">

                    <label class="block space-y-2">
                        <span class="text-sm font-medium">SEO Meta Title</span>
                        <input type="text" name="meta_title" maxlength="255"
                            value="{{ old('meta_title', $product->meta_title) }}"
                            placeholder="Example: Premium Gold Ring | Best Jewellery" class="{{ $inputClass }}">
                        <span class="block text-xs text-zinc-500">Recommended around 50–60 characters.</span>
                    </label>

                    <label class="block space-y-2">
                        <span class="text-sm font-medium">SEO Meta Description</span>
                        <textarea name="meta_description" rows="3" maxlength="1000"
                            placeholder="Write a short description for search engines." class="{{ $inputClass }}">{{ old('meta_description', $product->meta_description) }}</textarea>
                        <span class="block text-xs text-zinc-500">Recommended around 150–160 characters.</span>
                    </label>

                    <label class="block space-y-2">
                        <span class="text-sm font-medium">SEO / Search Keywords</span>
                        <input type="text" name="meta_keywords" maxlength="1000"
                            value="{{ old('meta_keywords', $product->meta_keywords) }}"
                            placeholder="gold ring, jewellery, 22k gold, designer ring" class="{{ $inputClass }}">
                        <span class="block text-xs text-zinc-500">Separate keywords with commas.</span>
                    </label>

                </div>
            </div>

            {{-- Product Specifications --}}
            <div id="product-specifications-section"
                class="rounded-2xl border border-zinc-200 p-5 dark:border-zinc-700">

                <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <h2 class="text-lg font-semibold">Product Specifications</h2>
                        <p class="mt-1 text-sm text-zinc-500">
                            Add product details like Brand, Material, Weight or Warranty.
                        </p>
                    </div>

                    <button type="button" id="add-specification-row"
                        class="rounded-xl border border-zinc-300 px-4 py-2 text-sm font-medium dark:border-zinc-700">
                        + Add Specification
                    </button>
                </div>

                @php
                    $specifications = old('specifications', $product->specifications ?? []);
                @endphp

                <div id="specifications-container" class="space-y-3">

                    @foreach ($specifications as $index => $specification)
                        <div data-specification-row
                            class="grid gap-3 rounded-xl border border-zinc-200 p-3 dark:border-zinc-700 sm:grid-cols-[1fr_1fr_auto]">

                            <input type="text" name="specifications[{{ $index }}][name]"
                                value="{{ $specification['name'] ?? '' }}" placeholder="Specification Name"
                                maxlength="255"
                                class="rounded-xl border border-zinc-300 bg-white px-3 py-2 dark:border-zinc-700 dark:bg-zinc-800">

                            <input type="text" name="specifications[{{ $index }}][value]"
                                value="{{ $specification['value'] ?? '' }}" placeholder="Specification Value"
                                maxlength="1000"
                                class="rounded-xl border border-zinc-300 bg-white px-3 py-2 dark:border-zinc-700 dark:bg-zinc-800">

                            <button type="button" data-remove-specification
                                class="rounded-xl border border-red-300 px-4 py-2 text-sm font-medium text-red-600">
                                Remove
                            </button>
                        </div>
                    @endforeach

                </div>

                <p class="mt-3 text-xs text-zinc-500">
                    Maximum 30 specifications per product.
                </p>
            </div>

            <script>
                (function() {

                    function initializeProductSpecifications() {

                        const section = document.getElementById('product-specifications-section');

                        if (!section || section.dataset.initialized === '1') {
                            return;
                        }

                        section.dataset.initialized = '1';

                        const container = section.querySelector('#specifications-container');
                        const addButton = section.querySelector('#add-specification-row');

                        let nextIndex = 0;

                        container.querySelectorAll('[data-specification-row] input[name]').forEach(function(input) {
                            const match = input.name.match(/^specifications\[(\d+)\]/);

                            if (match) {
                                nextIndex = Math.max(nextIndex, Number(match[1]) + 1);
                            }
                        });

                        const inputClass =
                            'w-full rounded-xl border border-zinc-300 bg-white px-3 py-2 ' +
                            'dark:border-zinc-700 dark:bg-zinc-800';

                        function createInput(name, placeholder, maxLength) {
                            const input = document.createElement('input');

                            input.type = 'text';
                            input.name = name;
                            input.placeholder = placeholder;
                            input.maxLength = maxLength;
                            input.className = inputClass;

                            return input;
                        }

                        addButton.addEventListener('click', function() {

                            if (container.querySelectorAll('[data-specification-row]').length >= 30) {
                                alert('Maximum 30 specifications allowed.');
                                return;
                            }

                            const index = nextIndex++;
                            const row = document.createElement('div');

                            row.dataset.specificationRow = '1';
                            row.className =
                                'grid gap-3 rounded-xl border border-zinc-200 p-3 ' +
                                'dark:border-zinc-700 sm:grid-cols-[1fr_1fr_auto]';

                            row.appendChild(createInput(`specifications[${index}][name]`, 'Specification Name', 255));
                            row.appendChild(createInput(`specifications[${index}][value]`, 'Specification Value',
                            1000));

                            const removeButton = document.createElement('button');

                            removeButton.type = 'button';
                            removeButton.dataset.removeSpecification = '1';
                            removeButton.textContent = 'Remove';
                            removeButton.className =
                                'rounded-xl border border-red-300 px-4 py-2 text-sm font-medium text-red-600';

                            row.appendChild(removeButton);
                            container.appendChild(row);
                        });

                        container.addEventListener('click', function(event) {
                            const button = event.target.closest('[data-remove-specification]');

                            if (!button) {
                                return;
                            }

                            button.closest('[data-specification-row]')?.remove();
                        });
                    }

                    if (document.readyState === 'loading') {
                        document.addEventListener('DOMContentLoaded', initializeProductSpecifications, {
                            once: true
                        });
                    } else {
                        initializeProductSpecifications();
                    }

                    document.addEventListener('livewire:navigated', initializeProductSpecifications);

                })();
            </script>

            <div class="flex flex-wrap gap-3">
                <button type="submit"
                    class="rounded-xl bg-zinc-950 px-6 py-2.5 font-semibold text-white dark:bg-white dark:text-zinc-950">
                    {{ $product->exists ? 'Update Product' : 'Create Product' }}
                </button>

                <a href="{{ route('businesses.products.index', $business) }}"
                    class="rounded-xl border border-zinc-300 px-5 py-2.5 dark:border-zinc-700">
                    Cancel
                </a>
            </div>
        </form>
    </div>

</x-layouts::app>
