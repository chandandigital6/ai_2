<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Growya — Discover local. Grow together.</title>
    <meta name="description"
        content="Discover approved local businesses, products and services on Growya. Build your digital showroom and connect with more customers.">
    @include('public.partials.assets')
</head>

<body class="g-public">
    @php
        $storageUrl = static fn($path) => !$path
            ? null
            : (\Illuminate\Support\Str::startsWith($path, ['http://', 'https://'])
                ? $path
                : asset('storage/' . ltrim($path, '/')));
        $marketplaceUrl = route('public.marketplace.index');
        $showroomUrl = auth()->check() ? route('businesses.create') : route('register');
        $heroBusiness = $featuredBusinesses->first(fn($b) => filled($b->cover));
        $heroCoverUrl = $storageUrl($heroBusiness?->cover);
    @endphp
    @include('public.partials.site-header')
    <main id="main-content">
<x-growya.banners placement="home" :city="null" />
        <section class="g-home-hero">
            <div class="g-container g-home-hero-inner">
                <div><span class="g-eyebrow"><x-growya.icon name="sparkles" />Your neighbourhood. A world of
                        possibilities.</span>
                    <h1>Discover local.<br>Connect directly.<em>Grow together.</em></h1>
                    <p class="g-home-hero-description">Find great businesses, products and services around you. Or give
                        your own business a place to shine online.</p>
                    <form action="{{ route('public.search.index') }}" method="GET" class="g-search-box"><x-growya.icon
                            name="search" /><input type="hidden" name="type" value="businesses"><label
                            class="sr-only" for="home-search">Search local listings</label><input id="home-search"
                            type="search" name="q" maxlength="120"
                            placeholder="What are you looking for today?"><button class="g-btn g-btn-primary"
                            type="submit">Search<x-growya.icon name="arrow-right" /></button></form>
                    @if ($categories->isNotEmpty())
                        <div class="g-popular"><span>Popular:</span>
                            @foreach ($categories->take(3) as $category)
                                <a
                                    href="{{ route('public.marketplace.index', ['category' => $category->slug]) }}">{{ $category->name }}</a>
                            @endforeach
                        </div>
                    @endif
                    <div class="g-home-stats">
                        <div><strong>{{ number_format($stats['businesses']) }}</strong><span>Approved businesses</span>
                        </div>
                        <div><strong>{{ number_format($stats['products']) }}</strong><span>Products to discover</span>
                        </div>
                        <div><strong>{{ number_format($stats['services']) }}</strong><span>Local services</span></div>
                    </div>
                </div>
                <div class="g-showcase"><span class="g-showcase-pill"><x-growya.icon name="shield" />Real businesses.
                        Real connections.</span>
                    <div class="g-showcase-card">
                        <div class="g-showcase-cover">
                            @if ($heroCoverUrl)
                                <img src="{{ $heroCoverUrl }}" alt="{{ $heroBusiness->name }} showroom"
                                fetchpriority="high">@else<div class="g-hero-art" aria-hidden="true">
                                    <div class="g-art-store">
                                        <div class="g-art-awning"></div>
                                        <div class="g-art-sign">LOCAL & LOVELY</div>
                                        <div class="g-art-window"><span></span><span></span></div>
                                    </div>
                                    <div class="g-art-plant"></div>
                                </div>
                            @endif
                            <div class="g-showcase-cover-caption">
                                <small>{{ $heroBusiness?->category?->name ?? 'Made for your neighbourhood' }}</small>
                                <h2>{{ $heroBusiness?->name ?? 'Every local business has a story.' }}</h2>
                            </div>
                        </div>
                        <div class="g-showcase-info">
                            <div>
                                <strong>{{ $heroBusiness ? 'Explore their digital showroom' : 'Let yours be discovered.' }}</strong><span>Products
                                    · Services · Direct connections</span></div><a class="g-icon-button"
                                aria-label="{{ $heroBusiness ? 'Visit showroom' : 'Explore marketplace' }}"
                                href="{{ $heroBusiness ? route('public.business.home', $heroBusiness->slug) : $marketplaceUrl }}"><x-growya.icon
                                    name="arrow-up-right" /></a>
                        </div>
                    </div>
                    <div class="g-showcase-floating"><span class="g-icon-box"><x-growya.icon name="store" /></span>
                        <div><strong>Your business, beautifully online.</strong><small>One showroom. More
                                possibilities.</small></div>
                    </div>
                </div>
            </div>
        </section>
        <div class="g-container">
            <div class="g-feature-grid">
                <div class="g-feature"><span class="g-icon-box"><x-growya.icon name="shield" /></span>
                    <div>
                        <h3>Approved business listings</h3>
                        <p>Explore businesses reviewed before they go live.</p>
                    </div>
                </div>
                <div class="g-feature"><span class="g-icon-box g-tone-blue"><x-growya.icon name="chat" /></span>
                    <div>
                        <h3>Connect directly</h3>
                        <p>Enquire, call or message the business you discover.</p>
                    </div>
                </div>
                <div class="g-feature"><span class="g-icon-box g-tone-amber"><x-growya.icon name="store" /></span>
                    <div>
                        <h3>A showroom of your own</h3>
                        <p>Your products and services, together in one place.</p>
                    </div>
                </div>
            </div>
        </div>
        <section class="g-section g-section-white" id="categories">
            <div class="g-container">
                <div class="g-section-heading">
                    <div>
                        <p class="g-kicker">Find your next discovery</p>
                        <h2>A little of everything local.</h2>
                    </div><a class="g-link" href="{{ $marketplaceUrl }}">All categories<x-growya.icon
                            name="arrow-right" /></a>
                </div>
                <div class="g-category-grid">
                    @forelse($categories as $category)
                        <a class="g-category"
                            href="{{ route('public.marketplace.index', ['category' => $category->slug]) }}"><span
                                class="g-icon-box g-tone-{{ ['green', 'blue', 'amber', 'rose', 'violet', 'green'][$loop->index % 6] }}">
                                @if ($category->image)
                                    <img src="{{ $storageUrl($category->image) }}" alt=""
                                    loading="lazy">@else<x-growya.icon :name="['store', 'bag', 'sparkles', 'box', 'grid', 'heart'][$loop->index % 6]" />
                                @endif
                            </span>
                        <strong>{{ $category->name }}</strong><small>Explore businesses</small></a>@empty<div
                            style="grid-column:1/-1"><x-growya.empty title="Your local marketplace is growing"
                                description="Categories will appear as approved businesses join." icon="store" />
                        </div>
                    @endforelse
                </div>
            </div>
        </section>
        <section class="g-section" id="businesses">
            <div class="g-container">
                <div class="g-section-heading">
                    <div>
                        <p class="g-kicker">Discover the neighbourhood</p>
                        <h2>Businesses worth knowing.</h2>
                        <p>Explore local showrooms, products and services.</p>
                    </div><a class="g-link" href="{{ $marketplaceUrl }}">Explore all<x-growya.icon
                            name="arrow-right" /></a>
                </div>
                <div class="g-business-grid">
                    @forelse($featuredBusinesses as $business)
                        @include('public.partials.business-card', ['business' => $business])@empty
                        <div class="g-panel" style="grid-column:1/-1"><x-growya.empty
                                title="A place for local businesses"
                                description="Approved business showrooms will appear here." icon="store"
                                :href="$showroomUrl" action="Create your showroom" /></div>
                    @endforelse
                </div>
            </div>
        </section>
        <section class="g-section g-section-white" id="products">
            <div class="g-container">
                <div class="g-section-heading">
                    <div>
                        <p class="g-kicker">Picked from local shops</p>
                        <h2>Your next favourite find.</h2>
                    </div><a class="g-link" href="{{ route('public.search.index', ['type' => 'products']) }}">All
                        products<x-growya.icon name="arrow-right" /></a>
                </div>
                <div class="g-product-grid">
                    @forelse($featuredProducts as $product)
                        <a class="g-product-card"
                            href="{{ isset($paidProductPromotions[$product->id]) ? route('growya.promotions.click', $paidProductPromotions[$product->id]) : route('public.business.product', [$product->business->slug, $product->slug]) }}">
                            <div class="g-product-image">
                                @if (isset($paidProductPromotions[$product->id]))
                                    <span class="g-badge"
                                        style="position:absolute;z-index:2;right:10px;top:10px">Sponsored</span>
                                    @endif@if ($product->featured_image)
                                        <img src="{{ $storageUrl($product->featured_image) }}"
                                        alt="{{ $product->name }}" loading="lazy">@else<x-growya.icon
                                            name="box" />
                                    @endif
                            </div>
                            <div class="g-product-info">
                                <h3>{{ $product->name }}</h3>
                                <p>{{ $product->business->name }}</p>
                                @if (($product->offer_price ?? $product->price) !== null)
                                    <strong
                                    class="g-product-price">₹{{ number_format((float) ($product->offer_price ?? $product->price), 2) }}</strong>@else<span
                                        class="g-link">Enquire for price</span>
                                @endif
                            </div>
                    </a>@empty<div style="grid-column:1/-1"><x-growya.empty title="Great finds are on their way"
                                description="Featured products will appear as businesses add their catalogues."
                                icon="box" /></div>
                    @endforelse
                </div>
            </div>
        </section>
        <section class="g-section" id="services">
            <div class="g-container">
                <div class="g-section-heading">
                    <div>
                        <p class="g-kicker">Expertise around you</p>
                        <h2>Local services. Personal connections.</h2>
                    </div><a class="g-link" href="{{ route('public.search.index', ['type' => 'services']) }}">All
                        services<x-growya.icon name="arrow-right" /></a>
                </div>
                <div class="g-business-grid">
                    @forelse($popularServices as $service)
                        <a class="g-service-card"
                            href="{{ route('public.business.service', [$service->business->slug, $service->slug]) }}"><span
                                class="g-icon-box g-tone-{{ ['green', 'blue', 'amber'][$loop->index % 3] }}"><x-growya.icon
                                    name="sparkles" /></span>
                            <h3>{{ $service->name }}</h3>
                            <p>{{ $service->business->name }}</p>
                            <p>{{ \Illuminate\Support\Str::limit(strip_tags($service->description ?? ''), 100) }}</p>
                            <span class="g-link">Explore service<x-growya.icon name="arrow-right" /></span>
                    </a>@empty<div class="g-panel" style="grid-column:1/-1"><x-growya.empty
                                title="Expertise, closer to home"
                                description="Local service listings will appear here." icon="sparkles" /></div>
                    @endforelse
                </div>
            </div>
        </section>
        @include('public.offers.partials.home-promotions')
        <section class="g-section" id="about">
            <div class="g-container">
                <div class="g-business-cta">
                    <div>
                        <p class="g-kicker">For the businesses that make a neighbourhood</p>
                        <h2>Local roots.<br>New possibilities.</h2>
                        <p>Give your business a digital showroom. Manage products, services, enquiries and orders from a
                            workspace built around you.</p>
                    </div><a class="g-btn g-btn-white" href="{{ $showroomUrl }}">Create your showroom<x-growya.icon
                            name="arrow-up-right" /></a>
                </div>
            </div>
        </section>
    </main>
    @include('public.partials.site-footer')
</body>

</html>
