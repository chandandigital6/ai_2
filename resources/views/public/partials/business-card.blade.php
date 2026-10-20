@php
    $cover = $business->cover ?: $business->logo;
    $coverUrl = $cover
        ? (\Illuminate\Support\Str::startsWith($cover, ['http://', 'https://'])
            ? $cover
            : asset('storage/' . ltrim($cover, '/')))
        : null;
    $location = $business->city?->name ?? ($business->address ?? 'Local business');

    $paidPromotion = $paidPromotions[$business->id] ?? null;
    $cardUrl = $paidPromotion
        ? route('growya.promotions.click', $paidPromotion)
        : route('public.business.home', $business->slug);

    $hasOffer = \App\Support\PublicOfferDisplay::forBusiness($business, 1)->isNotEmpty();

    $canClaim = auth()->check()
        && (int) auth()->id() !== (int) $business->owner_id
        && \Illuminate\Support\Facades\Route::has('growya.claims.create');
@endphp

{{--
    Pehle poora card ek <a> tha aur uske andar "Claim This Business" ka doosra <a> tha.
    Nested links HTML mein invalid hain: browser unhe tod deta hai, isliye claim link sahi nahi chalta tha.
    Ab card <article> hai, poore card ko "stretched link" cover karta hai, aur claim link uske upar alag (z-index) hai.
--}}
<article class="g-business-card" style="position:relative">
    <a href="{{ $cardUrl }}" aria-label="Visit {{ $business->name }} showroom"
        style="position:absolute;inset:0;z-index:1;border-radius:inherit"></a>

    <div class="g-business-cover">
        @if ($paidPromotion)
            <span class="g-badge" style="position:absolute;z-index:2;right:12px;top:12px">Sponsored</span>
        @endif

        @if ($coverUrl)
            <img src="{{ $coverUrl }}" alt="{{ $business->name }}" loading="lazy">
        @else
            <div class="g-business-cover-initial">{{ mb_strtoupper(mb_substr($business->name, 0, 2)) }}</div>
        @endif

        <span class="g-badge">
            <x-growya.icon name="shield" style="width:12px;height:12px" />Approved
        </span>
    </div>

    <div class="g-business-info">
        <p class="g-kicker">{{ $business->category?->name ?? 'Local business' }}</p>
        <h3>{{ $business->name }}</h3>

        @if ($hasOffer)
            <span class="g-badge g-tone-amber" style="margin-top:8px">Active offer</span>
        @endif

        <p class="g-business-location">
            <x-growya.icon name="pin" />{{ \Illuminate\Support\Str::limit($location, 65) }}
        </p>

        @if ($business->description)
            <p class="g-business-description">
                {{ \Illuminate\Support\Str::limit(strip_tags($business->description), 110) }}
            </p>
        @endif

        @if ($canClaim)
            <a href="{{ route('growya.claims.create', $business) }}"
                style="position:relative;z-index:2;display:inline-block;margin-top:12px;font-size:.8rem;font-weight:600;text-decoration:underline">
                Claim this business
            </a>
        @endif

        <div class="g-card-footer">
            <span>{{ number_format((int) $business->views_count) }} showroom views</span>
            <span class="g-link">Visit showroom<x-growya.icon name="arrow-up-right" /></span>
        </div>
    </div>
</article>
