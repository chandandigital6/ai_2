<x-layouts::app :title="$product->name">

    <div class="mx-auto max-w-5xl space-y-6 p-4 md:p-6">

        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h1 class="text-2xl font-bold">{{ $product->name }}</h1>
                <p class="text-sm text-zinc-500">{{ $business->name }}</p>
            </div>

            <a href="{{ route('businesses.products.index', $business) }}"
                class="rounded-xl border px-4 py-2 dark:border-zinc-700">
                Back to Products
            </a>
        </div>

        <div class="overflow-hidden rounded-2xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900">

            @if ($product->featured_image)
                <img src="{{ asset('storage/' . $product->featured_image) }}" alt="{{ $product->name }}"
                    class="max-h-96 w-full object-contain p-4">
            @endif

            <div class="space-y-5 p-6">

                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <p class="text-xs text-zinc-500">Category</p>
                        <p class="font-semibold">{{ $product->category?->name ?? 'N/A' }}</p>
                    </div>

                    <div>
                        <p class="text-xs text-zinc-500">SKU</p>
                        <p class="font-semibold">{{ $product->sku ?: 'N/A' }}</p>
                    </div>

                    <div>
                        <p class="text-xs text-zinc-500">Regular Price</p>
                        <p class="font-semibold">₹{{ number_format((float) $product->price, 2) }}</p>
                    </div>

                    <div>
                        <p class="text-xs text-zinc-500">Offer Price</p>
                        <p class="font-semibold">
                            {{ $product->offer_price !== null ? '₹' . number_format((float) $product->offer_price, 2) : 'No Offer' }}
                        </p>
                    </div>

                    <div>
                        <p class="text-xs text-zinc-500">Stock</p>
                        <p class="font-semibold">{{ $product->stock_quantity }} — {{ $product->stock_status }}</p>
                    </div>

                    <div>
                        <p class="text-xs text-zinc-500">Status</p>
                        <p class="font-semibold capitalize">{{ $product->status }}</p>
                    </div>
                </div>

                <div>
                    <h2 class="mb-2 font-semibold">Description</h2>
                    <p class="whitespace-pre-line text-sm text-zinc-600 dark:text-zinc-300">
                        {{ $product->description ?: 'No description added.' }}
                    </p>
                </div>

                <div class="space-y-3">
                    <h2 class="font-semibold">Product Tags</h2>

                    <div class="flex flex-wrap gap-2">
                        @forelse($product->tags as $tag)
                            <span class="rounded-full bg-zinc-100 px-3 py-1.5 text-sm dark:bg-zinc-800">
                                {{ $tag->name }}
                            </span>
                        @empty
                            <span class="text-sm text-zinc-500">
                                No tags assigned.
                            </span>
                        @endforelse
                    </div>
                </div>



                <div class="space-y-4">
                    <h2 class="text-lg font-semibold">
                        Product Attributes
                    </h2>

                    @if ($product->attributeValues->isNotEmpty())

                        <div class="grid gap-4 md:grid-cols-2">

                            @foreach ($product->attributeValues->groupBy('attribute_id') as $values)
                                <div class="rounded-xl border border-zinc-200 p-4 dark:border-zinc-700">

                                    <h3 class="mb-3 font-semibold">
                                        {{ $values->first()->attribute?->name }}
                                    </h3>

                                    <div class="flex flex-wrap gap-2">

                                        @foreach ($values as $value)
                                            <span class="rounded-full bg-zinc-100 px-3 py-1.5 text-sm dark:bg-zinc-800">
                                                {{ $value->value }}
                                            </span>
                                        @endforeach

                                    </div>
                                </div>
                            @endforeach

                        </div>
                    @else
                        <p class="text-sm text-zinc-500">
                            No attributes assigned.
                        </p>
                    @endif
                </div>



                {{-- Product Marketing Status --}}

                <div class="space-y-3">

                    <h2 class="text-lg font-semibold">
                        Product Marketing
                    </h2>

                    <div class="flex flex-wrap gap-2">

                        @if ($product->is_featured)
                            <span class="rounded-full bg-amber-100 px-3 py-1.5 text-sm font-semibold text-amber-800">
                                Featured Product
                            </span>
                        @endif

                        @if ($product->is_trending)
                            <span class="rounded-full bg-blue-100 px-3 py-1.5 text-sm font-semibold text-blue-800">
                                Trending Product
                            </span>
                        @endif

                        @if (!$product->is_featured && !$product->is_trending)
                            <span class="text-sm text-zinc-500">
                                Regular Product
                            </span>
                        @endif

                    </div>
                </div>


                {{-- Product Specifications --}}

                <div class="space-y-4">

                    <h2 class="text-lg font-semibold">
                        Product Specifications
                    </h2>

                    @if (!empty($product->specifications))

                        <div class="overflow-hidden rounded-xl border border-zinc-200 dark:border-zinc-700">

                            <table class="w-full text-left text-sm">

                                <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">

                                    @foreach ($product->specifications as $specification)
                                        <tr>
                                            <th class="w-1/3 bg-zinc-50 p-4 font-semibold dark:bg-zinc-800">
                                                {{ $specification['name'] ?? '' }}
                                            </th>

                                            <td class="p-4">
                                                {{ $specification['value'] ?? '' }}
                                            </td>
                                        </tr>
                                    @endforeach

                                </tbody>

                            </table>

                        </div>
                    @else
                        <p class="text-sm text-zinc-500">
                            No specifications added.
                        </p>

                    @endif

                </div>


                {{-- Product SEO Data --}}

                <div class="space-y-3 rounded-xl border border-zinc-200 p-5 dark:border-zinc-700">

                    <h2 class="text-lg font-semibold">
                        SEO Information
                    </h2>

                    <div>
                        <p class="text-xs text-zinc-500">Meta Title</p>
                        <p class="text-sm font-medium">
                            {{ $product->meta_title ?: 'Not added' }}
                        </p>
                    </div>

                    <div>
                        <p class="text-xs text-zinc-500">Meta Description</p>
                        <p class="whitespace-pre-line text-sm">
                            {{ $product->meta_description ?: 'Not added' }}
                        </p>
                    </div>

                    <div>
                        <p class="text-xs text-zinc-500">Search Keywords</p>
                        <p class="text-sm">
                            {{ $product->meta_keywords ?: 'Not added' }}
                        </p>
                    </div>

                </div>



                @if ($product->qr_code && \Illuminate\Support\Facades\Storage::disk('public')->exists($product->qr_code))
                    <div class="rounded-2xl border border-zinc-200 p-5 dark:border-zinc-700">
                        <h2 class="mb-4 text-lg font-semibold">
                            Product QR Code
                        </h2>

                        <img src="{{ asset('storage/' . $product->qr_code) }}"
                            alt="Product QR" class="h-40 w-40 rounded-xl bg-white p-2">

                        <a href="{{ route('businesses.products.qr.download', [$business, $product]) }}"
                            class="mt-3 inline-block text-sm font-semibold text-blue-600">
                            Download QR PNG
                        </a>
                    </div>
                @endif

                <div class="space-y-4">
                    <h2 class="text-lg font-semibold">
                        Product Gallery
                    </h2>

                    @if ($product->images->isNotEmpty())
                        <div class="grid grid-cols-2 gap-4 md:grid-cols-4">
                            @foreach ($product->images as $image)
                                <a href="{{ asset('storage/' . $image->image) }}" target="_blank"
                                    rel="noopener noreferrer">
                                    <img src="{{ asset('storage/' . $image->image) }}"
                                        alt="{{ $image->alt_text ?: $product->name }}"
                                        class="h-36 w-full rounded-xl border border-zinc-200 object-contain p-2 dark:border-zinc-700">
                                </a>
                            @endforeach
                        </div>
                    @else
                        <p class="text-sm text-zinc-500">
                            No additional gallery images available.
                        </p>
                    @endif
                </div>


                @if ($product->video)
                    <a href="{{ $product->video }}" target="_blank" rel="noopener noreferrer"
                        class="inline-block text-sm text-blue-600 hover:underline">
                        Open Product Video
                    </a>
                @endif

                <div class="flex flex-wrap gap-3 border-t border-zinc-200 pt-5 dark:border-zinc-700">

                    @if (app(\App\Services\BusinessAccessService::class)->can(auth()->user(), $business, 'products.update'))
                        <a href="{{ route('businesses.products.edit', [$business, $product]) }}"
                            class="rounded-xl bg-zinc-950 px-5 py-2.5 text-sm font-semibold text-white dark:bg-white dark:text-black">
                            Edit Product
                        </a>
                    @endif



                    <a href="{{ route('businesses.products.gallery.index', [$business, $product]) }}"
                        class="inline-flex items-center rounded-xl border border-zinc-300 px-5 py-2.5 text-sm font-semibold dark:border-zinc-700">
                        Manage Product Gallery
                    </a>

                    <a href="{{ route('businesses.products.variants.index', [$business, $product]) }}"
                        class="inline-flex items-center rounded-xl border border-zinc-300 px-5 py-2.5 text-sm font-semibold dark:border-zinc-700">
                        Manage Product Variants
                    </a>

                    <a href="{{ route('businesses.products.tags.index', [$business, $product]) }}"
                        class="inline-flex items-center rounded-xl border border-zinc-300 px-5 py-2.5 text-sm font-semibold dark:border-zinc-700">
                        Manage Product Tags
                    </a>

                    <a href="{{ route('businesses.products.attributes.index', [$business, $product]) }}"
                        class="inline-flex items-center rounded-xl border border-zinc-300 px-5 py-2.5 text-sm font-semibold dark:border-zinc-700">
                        Manage Product Attributes
                    </a>


                    <a href="{{ route('businesses.products.inventory.index', [$business, $product]) }}"
                        class="inline-flex items-center rounded-xl border border-zinc-300 px-5 py-2.5 text-sm font-semibold dark:border-zinc-700">
                        Manage Inventory
                    </a>

                    <a href="{{ route('businesses.products.qr.show', [$business, $product]) }}"
                        class="inline-flex items-center rounded-xl border border-zinc-300 px-5 py-2.5 text-sm font-semibold dark:border-zinc-700">
                        Product QR Code
                    </a>
                </div>
            </div>
        </div>

    </div>

</x-layouts::app>
