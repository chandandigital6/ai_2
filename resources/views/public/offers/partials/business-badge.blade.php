@php
    $businessOffers = \App\Support\PublicOfferDisplay::forBusiness($business, 1);
@endphp
@if($businessOffers->isNotEmpty())
    <a href="{{ route('public.business.offers', $business) }}"
       class="inline-flex items-center rounded-full bg-emerald-100 px-3 py-1 text-xs font-bold text-emerald-800 hover:bg-emerald-200">
        ✦ Active Offer
    </a>
@endif
