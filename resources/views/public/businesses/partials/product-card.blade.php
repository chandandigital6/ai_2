
@php
    $photo = $product->featured_image ?? $product->image ?? null;

    $photoUrl = $photo
        ? (\Illuminate\Support\Str::startsWith($photo, ['http://', 'https://'])
            ? $photo
            : asset('storage/' . ltrim($photo, '/')))
        : null;

    $price = $product->offer_price ?? $product->sale_price ?? $product->price ?? null;
@endphp

<a
    href="{{ route('public.business.product', [$business->slug, $product->slug]) }}"
    class="g-product-card group"
>
    <div class="g-product-image">

        @if($photoUrl)
            <img
                src="{{ $photoUrl }}"
                alt="{{ $product->name }}"
                loading="lazy"
                class="h-full w-full object-contain transition group-hover:scale-[1.03]"
            >
        @else
            <div class="flex h-full items-center justify-center text-sm text-zinc-400">
                Product Image
            </div>
        @endif

    </div>

    <div class="g-product-info">
        <h3 class="font-semibold">
            {{ $product->name }}
        </h3>

        @if($price !== null)
            <p class="mt-2 text-lg font-bold">
                ₹{{ number_format((float)$price, 2) }}
            </p>
        @else
            <p class="mt-2 text-sm text-zinc-500">
                Contact for Price
            </p>
        @endif

        <p class="mt-3 text-sm font-semibold text-emerald-700">
            View Details →
        </p>
    </div>
</a>
