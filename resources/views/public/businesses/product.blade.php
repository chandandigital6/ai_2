@extends('public.businesses.layout')

@section('title', ($product->meta_title ?: $product->name) . ' | ' . $business->name)
@section('meta_description', $product->meta_description ?:
    \Illuminate\Support\Str::limit(strip_tags($product->description ?: $product->name), 155))

@section('content')
    @php
        $assetUrl = function (?string $path) {
            if (!$path) {
                return null;
            }

            return \Illuminate\Support\Str::startsWith($path, ['http://', 'https://'])
                ? $path
                : asset('storage/' . ltrim($path, '/'));
        };

        // Photos
        $photos = collect();

        if ($product->featured_image) {
            $photos->push(['url' => $assetUrl($product->featured_image), 'alt' => $product->name]);
        }

        foreach ($product->images as $pic) {
            $url = $assetUrl($pic->image);

            if ($url) {
                $photos->push(['url' => $url, 'alt' => $pic->alt_text ?: $product->name]);
            }
        }

        $photos = $photos->unique('url')->values();

        // Price
        $price = (float) ($product->offer_price ?? $product->price);
        $basePrice = (float) $product->price;
        $discount =
            $product->offer_price !== null && $basePrice > $price && $basePrice > 0
                ? round((($basePrice - $price) / $basePrice) * 100)
                : null;

        // Contact
        $whatsapp = preg_replace('/\D/', '', $business->whatsapp ?: '');

        if (strlen($whatsapp) === 10) {
            $whatsapp = '91' . $whatsapp;
        }

        if (strlen($whatsapp) < 11 || strlen($whatsapp) > 15) {
            $whatsapp = '';
        }

        $phone = preg_replace('/[^0-9+]/', '', $business->phone ?: '');
        $shareUrl = route('public.business.product', [$business->slug, $product->slug]);
        $sellerMessage = 'Hello, I am interested in ' . $product->name . ' (' . $shareUrl . '). Please share details.';

        // Sirf active variants
        $availableVariants = $product->variants->filter(fn($v) => ($v->status ?? 'active') === 'active')->values();
        $hasVariants = $availableVariants->isNotEmpty();

        $inStock = $hasVariants
            ? $availableVariants->contains(fn($v) => (int) $v->stock_quantity > 0)
            : $product->stock_status === 'in_stock' && (int) $product->stock_quantity > 0;

        $baseStock = (int) $product->stock_quantity;
        $lowStock = !$hasVariants && $inStock && $baseStock <= 5;
        $qtyMax = $hasVariants ? 100 : max(1, min(100, $baseStock));

        // Specifications: [{name, value}] ya {key: value}, dono chalenge
        $specRows = collect($product->specifications ?? [])
            ->map(function ($value, $key) {
                if (is_array($value)) {
                    return [
                        'name' => $value['name'] ?? null,
                        'value' => $value['value'] ?? null,
                    ];
                }

                return [
                    'name' => is_string($key) ? $key : null,
                    'value' => is_scalar($value) ? $value : null,
                ];
            })
            ->filter(fn($row) => filled($row['name']) && filled($row['value']))
            ->values();

        // Video: direct file ho to player, warna link
        $videoUrl = $product->video ? $assetUrl($product->video) : null;
        $isDirectVideo =
            $videoUrl &&
            in_array(strtolower(pathinfo(parse_url($videoUrl, PHP_URL_PATH) ?? '', PATHINFO_EXTENSION)), [
                'mp4',
                'webm',
                'ogg',
                'mov',
            ]);
    @endphp

    <div class="mx-auto max-w-7xl px-4 py-8 lg:px-8">

        {{-- Flash messages --}}
        @if (session('success'))
            <div class="mb-5 rounded-xl bg-emerald-50 p-4 text-sm font-semibold text-emerald-800" role="status">
                {{ session('success') }}
            </div>
        @endif

        @if (session('error') || $errors->any())
            <div class="mb-5 rounded-xl bg-red-50 p-4 text-sm text-red-700" role="alert">
                {{ session('error') }}
                @foreach ($errors->all() as $message)
                    <div>{{ $message }}</div>
                @endforeach
            </div>
        @endif

        {{-- Breadcrumb --}}
        <nav class="mb-7 flex flex-wrap gap-2 text-sm text-slate-500" aria-label="Breadcrumb">
            @if (\Illuminate\Support\Facades\Route::has('public.marketplace.index'))
                <a class="hover:text-emerald-700" href="{{ route('public.marketplace.index') }}">Marketplace</a>
                <span>/</span>
            @endif
            <a class="hover:text-emerald-700"
                href="{{ route('public.business.home', $business->slug) }}">{{ $business->name }}</a>
            <span>/</span>
            <a class="hover:text-emerald-700" href="{{ route('public.business.products', $business->slug) }}">Products</a>
            <span>/</span>
            <span class="font-semibold text-slate-800">{{ $product->name }}</span>
        </nav>

        <div class="grid gap-9 lg:grid-cols-2">

            {{-- Images --}}
            <section class="min-w-0 lg:sticky lg:top-6 lg:self-start" aria-label="Product images">
                <div class="relative overflow-hidden rounded-3xl border border-slate-200 bg-white">
                    @if ($photos->isNotEmpty())
                        <img data-main-product-image src="{{ $photos[0]['url'] }}" alt="{{ $photos[0]['alt'] }}"
                            class="aspect-square w-full object-contain p-5">
                    @else
                        <div class="flex aspect-square items-center justify-center text-sm text-slate-400">
                            No product image available
                        </div>
                    @endif

                    @if ($product->is_featured)
                        <span
                            class="absolute left-4 top-4 rounded-full bg-emerald-100 px-3 py-1 text-xs font-bold text-emerald-800">
                            Featured
                        </span>
                    @endif
                </div>

                @if ($photos->count() > 1)
                    <div class="mt-4 grid grid-cols-5 gap-3">
                        @foreach ($photos as $photo)
                            <button type="button" data-photo-url="{{ $photo['url'] }}" data-photo-alt="{{ $photo['alt'] }}"
                                aria-label="Show image {{ $loop->iteration }}"
                                class="product-thumb overflow-hidden rounded-xl border-2 {{ $loop->first ? 'border-emerald-600' : 'border-slate-200' }} bg-white">
                                <img src="{{ $photo['url'] }}" alt="{{ $photo['alt'] }}" loading="lazy"
                                    class="aspect-square w-full object-contain">
                            </button>
                        @endforeach
                    </div>
                @endif

                @if ($videoUrl)
                    <div class="mt-5 rounded-2xl border border-slate-200 bg-white p-4">
                        <p class="mb-2 text-sm font-bold">Product Video</p>

                        @if ($isDirectVideo)
                            <video controls preload="metadata" playsinline class="w-full rounded-lg"
                                src="{{ $videoUrl }}"></video>
                        @else
                            <a href="{{ $videoUrl }}" target="_blank" rel="noopener noreferrer"
                                class="inline-block rounded-xl border border-slate-300 px-4 py-2 text-sm font-bold text-emerald-700">
                                ▶ Watch Video
                            </a>
                        @endif
                    </div>
                @endif
            </section>

            {{-- Details --}}
            <section class="min-w-0 space-y-6 py-1">

                {{-- Title + price --}}
                <div>
                    <a href="{{ route('public.business.home', $business->slug) }}"
                        class="text-sm font-bold text-emerald-700">← {{ $business->name }}</a>

                    @if ($product->category)
                        <div class="mt-5 text-xs font-bold uppercase tracking-widest text-slate-400">
                            {{ $product->category->name }}
                        </div>
                    @endif

                    <h1 class="mt-2 text-3xl font-black leading-tight text-slate-950 md:text-4xl">
                        {{ $product->name }}
                    </h1>

                    <div class="mt-5 flex flex-wrap items-end gap-3">
                        <strong id="display-price" class="text-3xl font-black text-slate-950">
                            ₹{{ number_format($price, 2) }}
                        </strong>

                        <span id="base-price" class="pb-1 text-slate-400 line-through {{ $discount ? '' : 'hidden' }}">
                            ₹{{ number_format($basePrice, 2) }}
                        </span>

                        <span id="discount-badge"
                            class="mb-1 rounded-full bg-emerald-50 px-3 py-1 text-xs font-bold text-emerald-700 {{ $discount ? '' : 'hidden' }}">
                            {{ $discount }}% OFF
                        </span>
                    </div>

                    @if ($product->description)
                        <p class="mt-5 whitespace-pre-line leading-8 text-slate-600">{{ $product->description }}</p>
                    @endif
                </div>

                @include('public.offers.partials.product-promotions')

                {{-- Availability --}}
                <div class="rounded-2xl border border-slate-200 bg-white p-5">
                    <div class="flex items-center justify-between gap-4">
                        <span class="text-sm font-semibold text-slate-600">Availability</span>
                        <strong id="stock-badge" class="text-sm {{ $inStock ? 'text-emerald-700' : 'text-amber-700' }}">
                            @if (!$inStock)
                                Currently Unavailable
                            @elseif ($lowStock)
                                Only {{ $baseStock }} left
                            @else
                                In Stock
                            @endif
                        </strong>
                    </div>

                    @if ($product->sku)
                        <div class="mt-3 flex justify-between gap-4 border-t pt-3 text-sm">
                            <span class="font-semibold">SKU</span>
                            <span id="variant-sku" class="text-slate-600">{{ $product->sku }}</span>
                        </div>
                    @endif
                </div>

                {{-- Attributes (variants na ho to) --}}
                @if (!$hasVariants && $product->attributeValues->isNotEmpty())
                    <div class="rounded-2xl border border-slate-200 bg-white p-5">
                        <p class="mb-3 text-sm font-bold">Product Attributes</p>

                        <div class="flex flex-wrap gap-2">
                            @foreach ($product->attributeValues as $value)
                                <span class="rounded-full bg-slate-100 px-3 py-2 text-xs font-semibold">
                                    {{ $value->attribute?->name }}: {{ $value->value }}
                                </span>
                            @endforeach
                        </div>
                    </div>
                @endif

                {{-- Variant + quantity + cart (ek hi form, ek hi variant selector) --}}
                <form id="cart-form" method="POST" action="{{ route('growya.cart.add', $business) }}"
                    class="space-y-5 rounded-2xl border border-slate-200 bg-white p-5">
                    @csrf
                    <input type="hidden" name="product_id" value="{{ $product->id }}">

                    @if ($hasVariants)
                        <div>
                            <label for="product-variant" class="block text-sm font-bold">Select Variant</label>

                            <select id="product-variant" name="variant_id" required
                                class="mt-3 w-full rounded-xl border border-slate-300 bg-white px-4 py-3 focus:border-emerald-600 focus:outline-none focus:ring-2 focus:ring-emerald-100">
                                <option value="">Select a variant</option>

                                @foreach ($availableVariants as $variant)
                                    @php
                                        $values = method_exists($variant, 'attributeValues')
                                            ? $variant->attributeValues
                                                ->map(fn($v) => ($v->attribute?->name ?: 'Attribute') . ': ' . $v->value)
                                                ->implode(' · ')
                                            : '';

                                        $variantPrice = (float) ($variant->offer_price ?? $variant->price);
                                        $variantOut = (int) $variant->stock_quantity <= 0;
                                    @endphp

                                    <option value="{{ $variant->id }}" data-price="{{ $variantPrice }}"
                                        data-original="{{ (float) $variant->price }}"
                                        data-stock="{{ (int) $variant->stock_quantity }}"
                                        data-sku="{{ $variant->sku }}" data-image="{{ $assetUrl($variant->image) }}"
                                        data-name="{{ $variant->name }}" @disabled($variantOut)>
                                        {{ $variant->name }}{{ $values ? ' — ' . $values : '' }} —
                                        ₹{{ number_format($variantPrice, 2) }}{{ $variantOut ? ' (Out of Stock)' : '' }}
                                    </option>
                                @endforeach
                            </select>

                            <p id="variant-note" class="mt-2 text-xs text-slate-500">
                                Pricing and availability update when you select a variant.
                            </p>
                        </div>
                    @endif

                    <div>
                        <label for="cart-qty" class="block text-sm font-bold">Quantity</label>

                        <div class="mt-3 inline-flex items-center overflow-hidden rounded-xl border border-slate-300">
                            <button type="button" data-qty-step="-1" aria-label="Decrease quantity"
                                class="px-4 py-2 text-lg font-bold hover:bg-slate-100">−</button>
                            <input id="cart-qty" type="number" name="quantity" value="1" min="1"
                                max="{{ $qtyMax }}" required inputmode="numeric"
                                class="w-16 border-x border-slate-300 py-2 text-center focus:outline-none [appearance:textfield] [&::-webkit-inner-spin-button]:appearance-none [&::-webkit-outer-spin-button]:appearance-none">
                            <button type="button" data-qty-step="1" aria-label="Increase quantity"
                                class="px-4 py-2 text-lg font-bold hover:bg-slate-100">+</button>
                        </div>
                    </div>

                    <div class="flex flex-wrap gap-3">
                        <button id="cart-submit" type="submit" @disabled(!$inStock)
                            class="flex-1 rounded-xl bg-emerald-700 px-5 py-3 font-semibold text-white transition hover:bg-emerald-800 disabled:cursor-not-allowed disabled:opacity-50">
                            {{ $inStock ? 'Add to Cart' : 'Out of Stock' }}
                        </button>

                        <a href="{{ route('public.products.enquiry.create', [$business, $product]) }}"
                            class="flex-1 rounded-xl border border-emerald-700 px-5 py-3 text-center font-semibold text-emerald-700 transition hover:bg-emerald-50">
                            Enquire about this product
                        </a>
                    </div>
                </form>

                {{-- Wishlist --}}
                @include('public.partials.wishlist-save', ['type' => 'product', 'itemId' => $product->id])

                {{-- Specifications --}}
                @if ($specRows->isNotEmpty())
                    <div class="rounded-2xl border border-slate-200 bg-white p-5">
                        <h2 class="text-base font-bold">Specifications</h2>

                        <dl class="mt-3 divide-y divide-slate-100">
                            @foreach ($specRows as $row)
                                <div class="flex justify-between gap-4 py-2 text-sm">
                                    <dt class="text-slate-500">{{ $row['name'] }}</dt>
                                    <dd class="text-right font-semibold">{{ $row['value'] }}</dd>
                                </div>
                            @endforeach
                        </dl>
                    </div>
                @endif

                {{-- Contact --}}
                <div>
                    <div class="grid gap-3 sm:grid-cols-2">
                        @if ($whatsapp)
                            <a id="seller-whatsapp" data-wa-base="https://wa.me/{{ $whatsapp }}"
                                data-wa-msg="{{ $sellerMessage }}"
                                href="https://wa.me/{{ $whatsapp }}?text={{ rawurlencode($sellerMessage) }}"
                                target="_blank" rel="noopener noreferrer"
                                class="rounded-2xl bg-emerald-600 px-5 py-4 text-center font-black text-white">
                                WhatsApp Seller
                            </a>
                        @endif

                        @if ($phone)
                            <a href="tel:{{ $phone }}"
                                class="rounded-2xl bg-slate-950 px-5 py-4 text-center font-black text-white">
                                Call Seller
                            </a>
                        @endif

                        @if (!$whatsapp && !$phone)
                            <a href="{{ route('public.business.contact', $business->slug) }}"
                                class="rounded-2xl bg-slate-950 px-5 py-4 text-center font-black text-white">
                                Contact Business
                            </a>
                        @endif
                    </div>

                    <p class="mt-3 text-xs leading-5 text-slate-500">
                        Contact buttons start an enquiry; they do not place an order or reserve stock.
                    </p>
                </div>

                @auth
    <form method="POST" action="{{ route('growya.chat.start', $business) }}">
        @csrf
        <input type="hidden" name="context_type" value="product">
        <input type="hidden" name="context_id" value="{{ $product->id }}">

        <button type="submit" class="rounded-xl bg-emerald-700 px-5 py-3 text-white">
            Chat With Seller
        </button>
    </form>
@else
    <a href="{{ route('login') }}">
        Login to Chat With Seller
    </a>
@endauth

                <div class="border-t border-slate-200 pt-5">
                    <h2 class="font-black text-slate-900">Sold by {{ $business->name }}</h2>
                    <a class="mt-2 inline-block text-sm font-bold text-emerald-700"
                        href="{{ route('public.business.home', $business->slug) }}">
                        Visit Business Showroom →
                    </a>
                </div>
            </section>

        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // ---------- Gallery ----------
            const main = document.querySelector('[data-main-product-image]');
            const thumbs = document.querySelectorAll('[data-photo-url]');

            const markThumb = function(button) {
                thumbs.forEach(function(b) {
                    b.classList.remove('border-emerald-600');
                    b.classList.add('border-slate-200');
                });
                if (button) {
                    button.classList.add('border-emerald-600');
                    button.classList.remove('border-slate-200');
                }
            };

            thumbs.forEach(function(button) {
                button.addEventListener('click', function() {
                    if (!main) return;
                    main.src = button.dataset.photoUrl;
                    main.alt = button.dataset.photoAlt || 'Product image';
                    markThumb(button);
                });
            });

            // ---------- Quantity + cart button ----------
            const qty = document.getElementById('cart-qty');
            const cartBtn = document.getElementById('cart-submit');

            const setMaxQty = function(max) {
                qty.max = max;
                if (Number(qty.value) > max) qty.value = Math.max(1, max);
            };

            document.querySelectorAll('[data-qty-step]').forEach(function(btn) {
                btn.addEventListener('click', function() {
                    const next = Number(qty.value || 1) + Number(btn.dataset.qtyStep);
                    qty.value = Math.min(Number(qty.max), Math.max(1, next));
                });
            });

            const setCartState = function(ok) {
                cartBtn.disabled = !ok;
                cartBtn.textContent = ok ? 'Add to Cart' : 'Out of Stock';
            };

            // ---------- Variant selector ----------
            const select = document.getElementById('product-variant');
            if (!select) return;

            const price = document.getElementById('display-price');
            const base = document.getElementById('base-price');
            const discountBadge = document.getElementById('discount-badge');
            const badge = document.getElementById('stock-badge');
            const sku = document.getElementById('variant-sku');
            const wa = document.getElementById('seller-whatsapp');

            const originalPrice = @json((float) $product->price);
            const originalOffer = @json((float) ($product->offer_price ?? $product->price));
            const defaultStock = @json($inStock);
            const defaultSku = @json($product->sku);

            const formatter = n => '₹' + Number(n).toLocaleString('en-IN', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            });

            select.addEventListener('change', function() {
                const option = select.selectedOptions[0];
                const picked = Boolean(select.value);

                const current = picked ? Number(option.dataset.price) : originalOffer;
                const old = picked ? Number(option.dataset.original) : originalPrice;
                const stock = picked ? Number(option.dataset.stock) : 0;
                const available = picked ? stock > 0 : defaultStock;

                price.textContent = formatter(current);

                const pct = old > current && old > 0 ? Math.round(((old - current) / old) * 100) : 0;

                if (base) {
                    base.textContent = formatter(old);
                    base.classList.toggle('hidden', !pct);
                }

                if (discountBadge) {
                    discountBadge.textContent = pct + '% OFF';
                    discountBadge.classList.toggle('hidden', !pct);
                }

                badge.textContent = !available ?
                    'Currently Unavailable' :
                    (picked && stock <= 5 ? 'Only ' + stock + ' left' : 'In Stock');
                badge.classList.toggle('text-emerald-700', available);
                badge.classList.toggle('text-amber-700', !available);

                if (sku) sku.textContent = picked ? (option.dataset.sku || 'N/A') : (defaultSku || 'N/A');

                if (main && picked && option.dataset.image) main.src = option.dataset.image;
                if (picked && option.dataset.image) markThumb(null);

                setMaxQty(picked ? Math.max(1, Math.min(100, stock)) : 100);
                setCartState(available);

                if (wa) {
                    const msg = wa.dataset.waMsg + (picked ? '\nVariant: ' + (option.dataset.name || '') : '');
                    wa.href = wa.dataset.waBase + '?text=' + encodeURIComponent(msg);
                }
            });
        });
    </script>
@endsection
