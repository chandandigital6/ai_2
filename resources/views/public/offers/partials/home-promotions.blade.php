@php
    $homeOffers = \App\Support\PublicOfferDisplay::visible()
        ->with('business:id,name,slug')
        ->latest('id')
        ->limit(6)
        ->get();
@endphp
@if ($homeOffers->isNotEmpty())
    <section class="bg-emerald-50 py-14" id="growya-offers">
        <div class="mx-auto max-w-7xl px-4 lg:px-8">
            <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
                <h2 class="text-2xl font-black text-slate-900">Current Offers & Promotions</h2>
                <a href="{{ route('public.offers.index') }}" class="font-bold text-emerald-700">View all offers →</a>
            </div>
            <div class="grid gap-5 md:grid-cols-2 lg:grid-cols-3">
                @foreach ($homeOffers as $offer)
                    @include('public.offers.partials.offer-card')
                @endforeach
            </div>
        </div>
    </section>
@endif
