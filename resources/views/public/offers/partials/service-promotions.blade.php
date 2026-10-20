@php $itemOffers = \App\Support\PublicOfferDisplay::forService($service); @endphp
@if($itemOffers->isNotEmpty())
<section class="mt-6 space-y-3 rounded-2xl border border-emerald-200 bg-emerald-50 p-5">
    <h2 class="text-lg font-bold text-emerald-900">Available Service Promotions</h2>
    @foreach($itemOffers as $offer)
        <div class="border-t border-emerald-200 pt-3 first:border-0 first:pt-0">
            <p class="font-semibold text-slate-900">{{ $offer->title }}</p>
            <p class="text-sm text-emerald-800">
                @if($offer->offer_type==='percentage'){{ number_format((float)$offer->discount_value,0) }}% promotional discount
                @elseif($offer->offer_type==='fixed')₹{{ number_format((float)$offer->discount_value,2) }} promotional discount
                @else Special promotion @endif
            </p>
            <p class="text-xs text-slate-600">Valid until {{ $offer->ends_at?->format('d M Y') ?? 'withdrawn' }}</p>
        </div>
    @endforeach
    <p class="text-xs text-slate-600">Promotions are informational; service price is unchanged.</p>
</section>
@endif
