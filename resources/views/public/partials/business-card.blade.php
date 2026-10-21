@php
    $path = $business->cover ?: $business->logo;
    $coverUrl = $path ? (\Illuminate\Support\Str::startsWith($path,['https://','http://']) ? $path : asset('storage/'.ltrim($path,'/'))) : null;
    $logo = $business->logo ? (\Illuminate\Support\Str::startsWith($business->logo,['https://','http://']) ? $business->logo : asset('storage/'.ltrim($business->logo,'/'))) : null;
    $promotion = $paidPromotions[$business->id] ?? null;
    $isSponsored = $promotion || (bool)$business->is_sponsored;
    $cardUrl = $promotion ? route('growya.promotions.click',$promotion) : route('public.business.home',$business->slug);
    $location = $business->city?->name ?? ($business->address ?: 'Location not listed');
@endphp
<article class="g-business-card {{ $isSponsored ? 'g-sponsored-card' : '' }}" data-business-id="{{ $business->id }}" data-sponsored="{{ $isSponsored ? 'true' : 'false' }}">
    <div class="g-business-cover"><a href="{{ $cardUrl }}" aria-label="Explore {{ $business->name }}">@if($coverUrl)<img src="{{ $coverUrl }}" alt="{{ $business->name }}" loading="lazy" decoding="async">@else<div class="g-business-cover-initial">{{ mb_strtoupper(mb_substr($business->name,0,2)) }}</div>@endif</a><span class="g-badge g-verified-badge"><x-growya.icon name="shield" />Verified</span>@if($isSponsored)<span class="g-sponsored-badge">Sponsored</span>@endif</div>
    <div class="g-business-info"><div class="g-card-category">{{ $business->category?->name ?? 'Local business' }}<span class="g-card-logo">@if($logo)<img src="{{ $logo }}" alt="" loading="lazy">@else{{ mb_strtoupper(mb_substr($business->name,0,1)) }}@endif</span></div><h3><a href="{{ $cardUrl }}">{{ $business->name }}</a></h3><p class="g-business-location"><x-growya.icon name="pin" />{{ \Illuminate\Support\Str::limit($location,65) }}</p><p class="g-business-description">{{ \Illuminate\Support\Str::limit(strip_tags($business->description ?: 'Discover their products, services and more in the digital showroom.'),105) }}</p><div class="g-card-footer"><span>{{ number_format((int)$business->views_count) }} views</span><a class="g-link" href="{{ $cardUrl }}">Visit showroom <x-growya.icon name="arrow-up-right" /></a></div>@if(auth()->check() && (int)auth()->id() !== (int)$business->owner_id && Route::has('growya.claims.create'))<a class="g-claim-link" href="{{ route('growya.claims.create',$business) }}">Own this business? Claim it</a>@endif</div>
</article>
