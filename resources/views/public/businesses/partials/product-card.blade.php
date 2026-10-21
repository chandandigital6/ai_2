@php
    $shop = $business ?? $product->business;
    $photo = $product->featured_image ?: ($product->image ?? null);
    $photoUrl = $photo ? (\Illuminate\Support\Str::startsWith($photo,['https://','http://']) ? $photo : asset('storage/'.ltrim($photo,'/'))) : null;
    $price = $product->offer_price ?? $product->price;
    $promotion = $paidProductPromotions[$product->id] ?? null;
    $isSponsored = $promotion || (bool)$product->is_sponsored;
    $url = $promotion ? route('growya.promotions.click',$promotion) : route('public.business.product',[$shop->slug,$product->slug]);
    $discounted = $product->offer_price !== null && $product->price !== null && (float)$product->offer_price < (float)$product->price;
@endphp
<article class="g-product-card {{ $isSponsored ? 'g-sponsored-card' : '' }}" data-product-id="{{ $product->id }}" data-sponsored="{{ $isSponsored ? 'true' : 'false' }}"><a class="g-product-image" href="{{ $url }}" aria-label="View {{ $product->name }}">@if($photoUrl)<img src="{{ $photoUrl }}" alt="{{ $product->name }}" loading="lazy" decoding="async">@else<div class="g-product-placeholder"><x-growya.icon name="box" /><span>Image coming soon</span></div>@endif @if($isSponsored)<span class="g-sponsored-badge">Sponsored</span>@endif @if($discounted)<span class="g-offer-pill">Special price</span>@endif</a><div class="g-product-info"><p class="g-card-category">{{ $shop->name }}</p><h3><a href="{{ $url }}">{{ $product->name }}</a></h3><div class="g-product-price-row">@if($price !== null)<strong class="g-product-price">₹{{ number_format((float)$price,2) }}</strong>@if($discounted)<del>₹{{ number_format((float)$product->price,2) }}</del>@endif @else<span class="g-price-request">Price on request</span>@endif</div><a class="g-product-action" href="{{ $url }}">View product <x-growya.icon name="arrow-up-right" /></a></div></article>
