@php
    $dashboardUrl = auth()->check() ? route('dashboard') : route('register');
    $links = [['Discover', 'home'], ['Marketplace', 'public.marketplace.index'], ['Offers', 'public.offers.index'], ['Products', 'public.search.index']];
@endphp
<a href="#main-content" class="g-skip">Skip to content</a>
<header class="g-site-header"><div class="g-container g-site-header-inner">
    <a class="g-brand" href="{{ route('home') }}" aria-label="Growya home"><span class="g-brand-mark">g</span>growya<span class="g-brand-dot">.</span></a>
    <nav class="g-site-nav" aria-label="Main navigation">@foreach($links as [$label,$routeName])<a href="{{ $label === 'Products' ? route($routeName,['type'=>'products']) : route($routeName) }}" @if(request()->routeIs($routeName)) aria-current="page" @endif>{{ $label }}</a>@endforeach @if(Route::has('growya.nearby.index'))<a href="{{ route('growya.nearby.index') }}">Near me <x-growya.icon name="pin" /></a>@endif</nav>
    <div class="g-actions g-header-cta">@guest<a class="g-header-login" href="{{ route('login') }}">Sign in</a>@endguest @auth<a class="g-header-login" href="{{ route('public.business.following') }}">Following</a>@endauth<a class="g-btn g-btn-primary" href="{{ $dashboardUrl }}"><span class="g-cta-long">{{ auth()->check() ? 'My workspace' : 'List your business' }}</span><span class="g-cta-short">{{ auth()->check() ? 'Workspace' : 'Join' }}</span><x-growya.icon name="arrow-up-right" /></a></div>
    <details class="g-public-menu"><summary class="g-icon-button" aria-label="Open navigation"><x-growya.icon name="menu" /></summary><nav class="g-mobile-nav" aria-label="Mobile navigation">@foreach($links as [$label,$routeName])<a href="{{ $label==='Products' ? route($routeName,['type'=>'products']) : route($routeName) }}">{{ $label }}</a>@endforeach @if(Route::has('growya.nearby.index'))<a href="{{ route('growya.nearby.index') }}">Nearby businesses</a>@endif<a href="{{ auth()->check() ? route('dashboard') : route('login') }}">{{ auth()->check() ? 'My workspace' : 'Sign in' }}</a><a href="{{ $dashboardUrl }}">{{ auth()->check() ? 'Dashboard' : 'List your business' }}</a></nav></details>
</div></header>

<x-growya-web-push />
