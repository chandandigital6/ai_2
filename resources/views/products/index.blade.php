<x-layouts::app :title="'Products - ' . $business->name">

    <div class="mx-auto max-w-7xl space-y-6 p-4 md:p-6">

        <div class="flex flex-wrap items-center justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold">Product Management</h1>
                <p class="mt-1 text-sm text-zinc-500">
                    {{ $business->name }}
                </p>
            </div>

            <a
    href="{{ route('businesses.products.bulk.index', $business) }}"
    class="inline-flex items-center rounded-xl border border-zinc-300 px-5 py-2.5 text-sm font-medium dark:border-zinc-700"
>
    Bulk Import / Export
</a>

            @if (app(\App\Services\BusinessAccessService::class)->can(auth()->user(), $business, 'products.create'))
                <a href="{{ route('businesses.products.create', $business) }}"
                    class="rounded-xl bg-zinc-950 px-5 py-2.5 font-semibold text-white dark:bg-white dark:text-black">
                    + Add Product
                </a>
            @endif
        </div>

        @if (session('success'))
            <div class="rounded-xl bg-green-50 p-4 text-sm text-green-700 dark:bg-green-950 dark:text-green-200">
                {{ session('success') }}
            </div>
        @endif

        <form method="GET" class="flex flex-wrap gap-3 rounded-2xl border border-zinc-200 p-4 dark:border-zinc-700">

            <input type="text" name="search" value="{{ request('search') }}" placeholder="Search name or SKU"
                class="min-w-48 flex-1 rounded-xl border border-zinc-300 bg-white px-3 py-2 dark:border-zinc-700 dark:bg-zinc-900">

            <select name="status"
                class="rounded-xl border border-zinc-300 bg-white px-3 py-2 dark:border-zinc-700 dark:bg-zinc-900">
                <option value="">All Status</option>
                <option value="active" @selected(request('status') === 'active')>Active</option>
                <option value="inactive" @selected(request('status') === 'inactive')>Inactive</option>
            </select>
            <select name="product_type"
                class="rounded-xl border border-zinc-300 bg-white px-3 py-2 dark:border-zinc-700 dark:bg-zinc-900">
                <option value="">All Products</option>

                <option value="featured" @selected(request('product_type') === 'featured')>
                    Featured Products
                </option>

                <option value="trending" @selected(request('product_type') === 'trending')>
                    Trending Products
                </option>

            </select>

            <button class="rounded-xl bg-zinc-900 px-5 py-2 text-white dark:bg-white dark:text-black">
                Search
            </button>
        </form>

        <div class="overflow-x-auto rounded-2xl border border-zinc-200 dark:border-zinc-700">
            <table class="w-full text-left text-sm">
                <thead class="bg-zinc-100 dark:bg-zinc-800">
                    <tr>
                        <th class="p-4">Image</th>
                        <th class="p-4">Product</th>
                        <th class="p-4">Category</th>
                        <th class="p-4">Price</th>
                        <th class="p-4">Stock</th>
                        <th class="p-4">Status</th>
                        <th class="p-4">Actions</th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">

                    @forelse($products as $product)
                        <tr>
                            <td class="p-4">
                                @if ($product->featured_image)
                                    <img src="{{ asset('storage/' . $product->featured_image) }}"
                                        class="h-16 w-16 rounded-xl object-cover" alt="{{ $product->name }}">
                                @else
                                    <div
                                        class="flex h-16 w-16 items-center justify-center rounded-xl bg-zinc-100 text-xs dark:bg-zinc-800">
                                        No Image
                                    </div>
                                @endif
                            </td>

                            <td class="p-4">
                                {{-- <div class="font-semibold">{{ $product->name }}</div> --}}
                                <div class="mt-2 flex flex-wrap gap-2">

                                    @if ($product->is_featured)
                                        <span
                                            class="rounded-full bg-amber-100 px-2.5 py-1 text-xs font-semibold text-amber-800 dark:bg-amber-900 dark:text-amber-100">
                                            Featured
                                        </span>
                                    @endif

                                    @if ($product->is_trending)
                                        <span
                                            class="rounded-full bg-blue-100 px-2.5 py-1 text-xs font-semibold text-blue-800 dark:bg-blue-900 dark:text-blue-100">
                                            Trending
                                        </span>
                                    @endif

                                </div>
                                <div class="text-xs text-zinc-500">
                                    SKU: {{ $product->sku ?: 'N/A' }}
                                </div>
                            </td>

                            <td class="p-4">
                                {{ $product->category?->name ?? 'Uncategorized' }}
                            </td>

                            <td class="p-4">
                                <div class="font-semibold">
                                    ₹{{ number_format((float) ($product->offer_price ?? $product->price), 2) }}
                                </div>

                                @if ($product->offer_price !== null)
                                    <div class="text-xs text-zinc-500 line-through">
                                        ₹{{ number_format((float) $product->price, 2) }}
                                    </div>
                                @endif
                            </td>

                            <td class="p-4">
                                {{ $product->stock_quantity }}
                                <div class="text-xs text-zinc-500">
                                    {{ str_replace('_', ' ', ucfirst($product->stock_status)) }}
                                </div>
                            </td>

                            <td class="p-4 capitalize">
                                {{ $product->status }}
                            </td>

                            <td class="p-4">
                                <div class="flex flex-wrap gap-3">
                                    <a href="{{ route('businesses.products.show', [$business, $product]) }}"
                                        class="text-blue-600 dark:text-blue-400">
                                        View
                                    </a>

                                    @if (app(\App\Services\BusinessAccessService::class)->can(auth()->user(), $business, 'products.update'))
                                        <a href="{{ route('businesses.products.edit', [$business, $product]) }}"
                                            class="text-amber-600">
                                            Edit
                                        </a>
                                    @endif

                                    @if (app(\App\Services\BusinessAccessService::class)->can(auth()->user(), $business, 'products.delete'))
                                        <form method="POST"
                                            action="{{ route('businesses.products.destroy', [$business, $product]) }}"
                                            onsubmit="return confirm('Move this product to trash?')">
                                            @csrf
                                            @method('DELETE')

                                            <button class="text-red-600">
                                                Delete
                                            </button>
                                        </form>
                                    @endif

                                    <a href="{{ route('businesses.products.qr.show', [$business, $product]) }}"
                                        class="text-purple-600">
                                        QR Code
                                    </a>
                                    <form id="bulk-qr-form" method="POST"
                                        action="{{ route('businesses.products.qr.bulk-print', $business) }}"
                                        target="_blank">
                                        @csrf
                                        <input type="checkbox" name="product_ids[]" form="bulk-qr-form"
                                            value="{{ $product->id }}" class="h-4 w-4 rounded border-zinc-300">
                                        <button type="submit"
                                            class="rounded-xl border border-zinc-300 px-5 py-2.5 font-semibold dark:border-zinc-700">
                                            Print Selected QR Labels
                                        </button>
                                    </form>


                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="p-8 text-center text-zinc-500">
                                No products found.
                            </td>
                        </tr>
                    @endforelse

                </tbody>
            </table>
        </div>

        {{ $products->links() }}

    </div>

</x-layouts::app>
