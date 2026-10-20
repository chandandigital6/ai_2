@props(['title' => null])
<!doctype html><html lang="{{ str_replace('_', '-', app()->getLocale()) }}"><head>@include('partials.head')</head><body class="g-auth">
<div class="g-auth-shell">
    <aside class="g-auth-story"><a href="{{ route('home') }}" class="g-brand"><span class="g-brand-mark">g</span>growya<span class="g-brand-dot">.</span></a><div class="g-auth-story-body"><p class="g-kicker" style="color:#97c9a3">Local roots. New possibilities.</p><h2>Your next chapter<br>starts <span>right here.</span></h2><p>Discover businesses around you, connect with local expertise and give your own business a place to grow.</p><div class="g-auth-feature"><span class="g-icon-box"><x-growya.icon name="store" /></span>Your own digital showroom</div><div class="g-auth-feature"><span class="g-icon-box"><x-growya.icon name="chat" /></span>Direct customer connections</div><div class="g-auth-feature"><span class="g-icon-box"><x-growya.icon name="grid" /></span>One workspace for your business</div></div><div class="g-auth-story-foot">Discover local. Connect directly. Grow together.</div></aside>
    <section class="g-auth-content"><div class="g-auth-content-top"><div class="g-auth-mobile-brand"><a href="{{ route('home') }}" class="g-brand"><span class="g-brand-mark">g</span>growya<span class="g-brand-dot">.</span></a></div><a href="{{ route('public.marketplace.index') }}">Explore marketplace</a><a href="{{ route('home') }}">Back to home ↗</a></div><main id="main-content" class="g-auth-form"><p class="g-kicker">Welcome to Growya</p>{{ $slot }}</main><div class="g-auth-foot">© {{ date('Y') }} Growya. Built for local growth.</div></section>
</div>
@persist('toast')<flux:toast.group><flux:toast /></flux:toast.group>@endpersist
@fluxScripts
</body></html>
