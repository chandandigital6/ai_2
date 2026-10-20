@php
    $assetUrl = function (?string $path) {
        if (!$path) {
            return null;
        }

        return \Illuminate\Support\Str::startsWith($path, ['http://', 'https://'])
            ? $path
            : asset('storage/' . ltrim($path, '/'));
    };

    $lines = $cart->cart_items->map(function ($item) use ($assetUrl) {
        $unit = (float) ($item->variant?->offer_price
            ?? $item->variant?->price
            ?? $item->product?->offer_price
            ?? $item->product?->price
            ?? 0);

        $stock = $item->variant
            ? (int) $item->variant->stock_quantity
            : (int) ($item->product?->stock_quantity ?? 0);

        return (object) [
            'item' => $item,
            'unit' => $unit,
            'total' => $unit * (int) $item->quantity,
            'stock' => $stock,
            'unavailable' => !$item->product || $stock < 1,
            'overStock' => $item->product && $stock >= 1 && (int) $item->quantity > $stock,
            'image' => $assetUrl($item->variant?->image ?: $item->product?->featured_image),
        ];
    });

    $total = $lines->sum('total');
    $count = $lines->sum(fn ($l) => (int) $l->item->quantity);
    $hasIssue = $lines->contains(fn ($l) => $l->unavailable || $l->overStock);
@endphp

<x-layouts::app title="Shopping Cart">
    <div id="main-content" class="mx-auto max-w-6xl space-y-6 p-6">
        <div class="flex flex-wrap items-end justify-between gap-3">
            <div>
                <h1 class="text-3xl font-bold">Your Cart</h1>
                <p class="text-sm text-zinc-500">{{ $business->name }}</p>
            </div>
            <a href="{{ route('public.business.products', $business->slug) }}"
                class="text-sm font-semibold text-emerald-700 underline dark:text-emerald-400">← Continue Shopping</a>
        </div>

        @if (session('success'))
            <div class="rounded-xl bg-emerald-50 p-4 text-sm font-semibold text-emerald-800 dark:bg-emerald-950 dark:text-emerald-200" role="status">
                {{ session('success') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="rounded-xl bg-red-50 p-4 text-sm text-red-800 dark:bg-red-950 dark:text-red-200" role="alert">
                @foreach ($errors->all() as $error)
                    <p>{{ $error }}</p>
                @endforeach
            </div>
        @endif

        @if ($lines->isEmpty())
            <div class="rounded-2xl border border-dashed p-12 text-center dark:border-zinc-700">
                <p class="text-lg font-semibold">Your cart is empty.</p>
                <p class="mt-1 text-sm text-zinc-500">Kuch products add karo aur yahan dikhenge.</p>
                <a href="{{ route('public.business.products', $business->slug) }}"
                    class="mt-5 inline-block rounded-xl bg-emerald-700 px-5 py-3 font-semibold text-white hover:bg-emerald-800">
                    Browse Products
                </a>
            </div>
        @else
            <div class="grid gap-6 lg:grid-cols-3">
                {{-- Items --}}
                <div class="space-y-4 lg:col-span-2">
                    @foreach ($lines as $line)
                        @php $item = $line->item; @endphp
                        <article class="flex flex-wrap gap-4 rounded-2xl border bg-white p-4 shadow-sm dark:border-zinc-700 dark:bg-zinc-900 sm:flex-nowrap">
                            <div class="h-24 w-24 shrink-0 overflow-hidden rounded-xl border bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-800">
                                @if ($line->image)
                                    <img src="{{ $line->image }}" alt="{{ $item->product?->name }}" loading="lazy"
                                        class="h-full w-full object-contain">
                                @else
                                    <div class="flex h-full items-center justify-center text-xs text-zinc-400">No image</div>
                                @endif
                            </div>

                            <div class="min-w-0 flex-1 space-y-2">
                                <div class="flex flex-wrap items-start justify-between gap-2">
                                    <div class="min-w-0">
                                        <p class="font-bold">{{ $item->product?->name ?? 'Product no longer available' }}</p>
                                        @if ($item->variant)
                                            <p class="text-sm text-zinc-500">{{ $item->variant->name }}</p>
                                        @endif
                                        <p class="text-sm text-zinc-500">₹{{ number_format($line->unit, 2) }} each</p>
                                    </div>
                                    <p class="font-bold">₹{{ number_format($line->total, 2) }}</p>
                                </div>

                                @if ($line->unavailable)
                                    <p class="text-sm font-semibold text-red-600">Ye item ab available nahi hai. Remove kar do.</p>
                                @elseif ($line->overStock)
                                    <p class="text-sm font-semibold text-amber-600">Sirf {{ $line->stock }} stock bacha hai. Quantity kam karo.</p>
                                @endif

                                <div class="flex flex-wrap items-center gap-4">
                                    @unless ($line->unavailable)
                                        <form method="POST" action="{{ route('growya.cart.update', [$business, $item]) }}"
                                            class="flex items-center gap-2">
                                            @csrf @method('PATCH')
                                            <label for="qty-{{ $item->id }}" class="sr-only">Quantity</label>
                                            <input id="qty-{{ $item->id }}" name="quantity" type="number" min="1"
                                                max="{{ max(1, min(100, $line->stock)) }}" value="{{ $item->quantity }}"
                                                class="w-20 rounded-lg border p-2 text-center dark:border-zinc-700 dark:bg-zinc-900">
                                            <button type="submit"
                                                class="rounded-lg bg-zinc-800 px-3 py-2 text-sm font-semibold text-white hover:bg-zinc-700">Update</button>
                                        </form>
                                    @endunless

                                    <form method="POST" action="{{ route('growya.cart.remove', [$business, $item]) }}"
                                        onsubmit="return confirm('Remove this item from cart?')">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="text-sm font-semibold text-red-600 underline">Remove</button>
                                    </form>
                                </div>
                            </div>
                        </article>
                    @endforeach
                </div>

                {{-- Summary --}}
                <aside class="h-fit space-y-4 rounded-2xl border bg-white p-5 shadow-sm dark:border-zinc-700 dark:bg-zinc-900 lg:sticky lg:top-6">
                    <h2 class="text-lg font-bold">Order Summary</h2>

                    <dl class="space-y-2 text-sm">
                        <div class="flex justify-between">
                            <dt class="text-zinc-500">Items ({{ $count }})</dt>
                            <dd>₹{{ number_format($total, 2) }}</dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-zinc-500">Coupon discount</dt>
                            <dd class="text-zinc-500">Checkout par apply hoga</dd>
                        </div>
                    </dl>

                    <div class="flex justify-between border-t pt-4 text-lg font-bold dark:border-zinc-700">
                        <span>Estimated total</span>
                        <span>₹{{ number_format($total, 2) }}</span>
                    </div>

                    <p class="text-xs text-zinc-500">Price aur stock checkout ke time dobara check hote hain.</p>

                    @if ($hasIssue)
                        <p class="rounded-lg bg-amber-50 p-3 text-xs text-amber-800 dark:bg-amber-950 dark:text-amber-200">
                            Kuch items available nahi hain ya stock kam hai. Pehle unhe theek karo.
                        </p>
                        <span class="block cursor-not-allowed rounded-xl bg-zinc-300 px-5 py-3 text-center font-semibold text-zinc-600">
                            Continue to Checkout
                        </span>
                    @else
                        <a href="{{ route('growya.checkout.form', $business) }}"
                            class="block rounded-xl bg-emerald-700 px-5 py-3 text-center font-semibold text-white hover:bg-emerald-800">
                            Continue to Checkout
                        </a>
                    @endif
                </aside>
            </div>
        @endif
    </div>
</x-layouts::app>