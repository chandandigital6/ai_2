@extends('public.businesses.layout')
@section('title', 'Offers | ' . $business->name)
@section('content')
    <div class="mx-auto max-w-7xl space-y-7 px-4 py-10">
        <div>
            <h1 class="text-3xl font-bold">Offers & Promotions</h1>
            <p class="mt-2 text-zinc-500">{{ $business->name }}</p>
        </div>
        <div class="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
            @forelse($offers as $offer)
                <div class="overflow-hidden rounded-2xl border bg-white shadow-sm">
                    @if ($offer->banner)
                        <img class="aspect-video w-full object-cover"
                            src="{{ asset('storage/' . $offer->banner) }}"
                            alt="{{ $offer->title }}">
                    @endif
                    <div class="space-y-3 p-5">
                        <h2 class="text-xl font-bold">{{ $offer->title }}</h2>
                        <div class="font-semibold text-emerald-700">
                            @if ($offer->offer_type === 'percentage')
                                {{ rtrim(rtrim(number_format((float) $offer->discount_value, 2, '.', ''), '0'), '.') }}% discount
                            @elseif($offer->offer_type === 'fixed')
                                ₹{{ number_format((float) $offer->discount_value, 2) }} discount
                            @else
                                Special Promotion
                            @endif
                        </div>
                        @if ($offer->description)
                            <p class="whitespace-pre-line text-sm text-zinc-600">{{ $offer->description }}</p>
                        @endif
                        <p class="text-xs text-zinc-500">Valid: {{ $offer->starts_at?->format('d M Y') ?? 'Now' }} –
                            {{ $offer->ends_at?->format('d M Y') ?? 'Until withdrawn' }}</p>
                        @if ($offer->offer_products->isNotEmpty())
                            <div class="space-y-1">
                                <h3 class="text-sm font-semibold">Products</h3>
                                @foreach ($offer->offer_products as $link)
                                    @if ($link->product && $link->product->status === 'active' && !$link->product->trashed())
                                        <a class="block text-sm text-blue-600 underline"
                                            href="{{ route('public.business.product', [$business->slug, $link->product->slug]) }}">{{ $link->product->name }}</a>
                                    @endif
                                @endforeach
                            </div>
                        @endif
                        @if ($offer->offer_services->isNotEmpty())
                            <div class="space-y-1">
                                <h3 class="text-sm font-semibold">Services</h3>
                                @foreach ($offer->offer_services as $link)
                                    @if ($link->service && $link->service->status === 'active' && !$link->service->trashed())
                                        <a class="block text-sm text-blue-600 underline"
                                            href="{{ route('public.business.service', [$business->slug, $link->service->slug]) }}">{{ $link->service->name }}</a>
                                    @endif
                                @endforeach
                            </div>
                        @endif
                    </div>
                </div>
            @empty<div class="rounded-xl border bg-white p-10 text-zinc-500 md:col-span-2 lg:col-span-3">No active
                    offers at the moment.</div>
            @endforelse
        </div>{{ $offers->links() }}
    </div>
@endsection
