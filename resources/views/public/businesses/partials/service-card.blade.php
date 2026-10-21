@php
$shop = $business ?? $service->business;
$path = $service->featured_image;
$photo = $path ? (\Illuminate\Support\Str::startsWith($path,['https://','http://']) ? $path : asset('storage/'.ltrim($path,'/'))) : null;
$price = $service->offer_price ?? $service->price;
@endphp
<article class="g-service-card">@if($photo)<a class="g-service-photo" href="{{ route('public.business.service',[$shop->slug,$service->slug]) }}"><img src="{{ $photo }}" alt="{{ $service->name }}" loading="lazy"></a>@else<span class="g-icon-box g-tone-green"><x-growya.icon name="sparkles" /></span>@endif<div class="g-service-info"><p class="g-card-category">{{ $shop->name }}</p><h3><a href="{{ route('public.business.service',[$shop->slug,$service->slug]) }}">{{ $service->name }}</a></h3><p>{{ \Illuminate\Support\Str::limit(strip_tags($service->description ?? ''),100) }}</p>@if($price !== null)<strong class="g-product-price">₹{{ number_format((float)$price,2) }}</strong>@endif<a class="g-link" href="{{ route('public.business.service',[$shop->slug,$service->slug]) }}">Explore service <x-growya.icon name="arrow-up-right" /></a></div></article>
