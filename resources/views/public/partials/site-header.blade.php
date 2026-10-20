@php
    $dashboardUrl = auth()->check() ? route('dashboard') : route('register');
    $links = [
        ['Home', 'home'],
        ['Marketplace', 'public.marketplace.index'],
        ['Offers', 'public.offers.index'],
        ['Search', 'public.search.index'],
    ];
@endphp
<a href="#main-content" class="g-skip">
    Skip to content</a>
<header class="g-site-header">
    <div class="g-container g-site-header-inner">
        <a href="{{ route('home') }}" class="g-brand" aria-label="Growya home"><span
                class="g-brand-mark">g</span>growya<span class="g-brand-dot">.</span></a>
        <nav class="g-site-nav" aria-label="Main navigation">
            @foreach ($links as [$label, $routeName])
                <a href="{{ route($routeName) }}"
                    @if (request()->routeIs($routeName)) aria-current="page" @endif>{{ $label }}</a>
            @endforeach
            <a href="{{ route('home') }}#about">For businesses</a>
            @if (Route::has('growya.nearby.index'))
                <a href="{{ route('growya.nearby.index') }}">
                    Nearby Businesses
                </a>
            @endif
        </nav>
        <div class="g-actions g-header-cta">@guest<a class="g-link hidden sm:inline-flex"
                    href="{{ route('login') }}">Log in</a>@endguest @auth<a class="g-link hidden sm:inline-flex"
                href="{{ route('public.business.following') }}">Following</a>@endauth
            <a class="g-btn g-btn-primary" href="{{ $dashboardUrl }}">
                {{ auth()->check() ? 'My dashboard' : 'Get started' }}<x-growya.icon name="arrow-up-right"
                    class="hidden sm:block" /></a>
        </div>
        <details class="g-public-menu">
            <summary class="g-icon-button" aria-label="Open menu"><x-growya.icon name="menu" /></summary>
            <nav class="g-mobile-nav" aria-label="Mobile navigation">
                @foreach ($links as [$label, $routeName])
                    <a href="{{ route($routeName) }}"
                        @if (request()->routeIs($routeName)) aria-current="page" @endif>{{ $label }}</a>
                @endforeach
                <a href="{{ route('home') }}#products">
                    Products</a><a href="{{ route('home') }}#services">Services</a><a
                    href="{{ route('home') }}#about">For businesses</a>@guest<a href="{{ route('login') }}">Log
                        in</a>@endguest @auth<a href="{{ route('public.business.following') }}">Following</a>@endauth
                    <a href="{{ $dashboardUrl }}">{{ auth()->check() ? 'My dashboard' : 'Create account' }}</a>
                </nav>
            </details>
        </div>
    </header>
