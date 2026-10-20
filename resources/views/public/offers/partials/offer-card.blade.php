@php
    $banner = $offer->banner;

    $bannerUrl = $banner
        ? (\Illuminate\Support\Str::startsWith($banner, ['https://', 'http://'])
            ? $banner
            : asset('storage/' . ltrim($banner, '/')))
        : null;
@endphp
<article class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
    @if ($bannerUrl)
        <img src="{{ $bannerUrl }}" loading="lazy" alt="{{ $offer->title }}" class="aspect-video w-full object-cover">
    @endif
    <div class="space-y-3 p-5">
        <p class="text-xs font-bold uppercase tracking-wide text-emerald-700">Active promotion</p>
        <h3 class="text-lg font-bold text-slate-900">{{ $offer->title }}</h3>
        <p class="font-bold text-emerald-700">
            @if ($offer->offer_type === 'percentage')
                {{ rtrim(rtrim(number_format((float) $offer->discount_value, 2, '.', ''), '0'), '.') }}% promotional
                discount
            @elseif($offer->offer_type === 'fixed')
                ₹{{ number_format((float) $offer->discount_value, 2) }} promotional discount
            @else
                Special promotion
            @endif
        </p>
        @if ($offer->description)
            <p class="line-clamp-3 text-sm leading-6 text-slate-600">{{ $offer->description }}</p>
        @endif
        <p class="text-xs text-slate-500">
            Valid: {{ $offer->starts_at?->format('d M Y') ?? 'Now' }} –
            {{ $offer->ends_at?->format('d M Y') ?? 'Until withdrawn' }}
        </p>
        @if (isset($offer->business) && $offer->business)
            <a href="{{ route('public.business.offers', $offer->business) }}"
                class="inline-block text-sm font-bold text-emerald-700 hover:underline">{{ $offer->business->name }} ·
                View offer →</a>
        @endif
        <p class="text-xs text-slate-500">Offer is promotional; displayed product/service price may differ.</p>
    </div>
</article>
