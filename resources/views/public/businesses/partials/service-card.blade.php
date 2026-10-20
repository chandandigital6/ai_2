@php
    $photo = $service->featured_image;

    $photoUrl = $photo
        ? (\Illuminate\Support\Str::startsWith($photo, ['http://', 'https://'])
            ? $photo
            : asset('storage/' . ltrim($photo, '/')))
        : null;

    $price = $service->offer_price ?? $service->price;
@endphp

<div class="overflow-hidden rounded-2xl border border-zinc-200 bg-white">

    <a href="{{ route('public.business.service', [$business->slug, $service->slug]) }}">

        <div class="aspect-video bg-zinc-100">

            @if($photoUrl)
                <img
                    src="{{ $photoUrl }}"
                    alt="{{ $service->name }}"
                    loading="lazy"
                    class="h-full w-full object-cover"
                >
            @else
                <div class="flex h-full items-center justify-center text-zinc-400">
                    Service
                </div>
            @endif

        </div>
    </a>

    <div class="space-y-3 p-5">

        <h3 class="text-lg font-bold">
            {{ $service->name }}
        </h3>

        <p class="line-clamp-3 text-sm text-zinc-600">
            {{ $service->description }}
        </p>

        @if($price !== null)
            <p class="font-bold">
                ₹{{ number_format((float) $price, 2) }}
            </p>
        @else
            <p class="text-sm text-zinc-500">
                Price on Request
            </p>
        @endif

        <div class="flex flex-wrap gap-2">

            <a
                href="{{ route('public.business.service', [$business->slug, $service->slug]) }}"
                class="rounded-xl border border-zinc-300 px-4 py-2 text-sm font-semibold"
            >
                Details
            </a>

            <a
                href="{{ route('public.services.enquiries.create', [$business, $service]) }}"
                class="rounded-xl bg-zinc-950 px-4 py-2 text-sm font-semibold text-white"
            >
                Enquire Now
            </a>

        </div>

    </div>
</div>