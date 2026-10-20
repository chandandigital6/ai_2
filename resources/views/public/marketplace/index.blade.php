<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Discover local businesses | Growya</title>
    <meta name="description"
        content="Find approved local businesses and explore their products, services and digital showrooms.">
    @include('public.partials.assets')
</head>

<body class="g-public">
    @include('public.partials.site-header')
    <main id="main-content" class="g-container">
<x-growya.banners placement="marketplace" :city="request('city_id')" />
        <section class="g-hero g-marketplace-hero">
            <p class="g-kicker"><x-growya.icon name="pin" />Your local marketplace</p>
            <h1>Find a business. Make a connection.</h1>
            <p>Discover local shops and services, explore their showrooms and connect directly.</p>
            <form method="GET" action="{{ route('public.marketplace.index') }}" class="g-marketplace-search"><label
                    for="market-query"><x-growya.icon name="search" /><span class="sr-only">Search
                        businesses</span><input id="market-query" type="search" name="q" maxlength="120"
                        value="{{ request('q') }}" placeholder="Business, category or address"></label><label
                    for="market-city"><x-growya.icon name="pin" /><span class="sr-only">City</span><input
                        id="market-city" name="city" maxlength="120" value="{{ request('city') }}"
                        placeholder="Which city?"></label>
                @if (request('category'))
                    <input type="hidden" name="category" value="{{ request('category') }}">
                @endif
                <input type="hidden" name="sort" value="{{ request('sort', 'newest') }}">
                <button type="submit" class="g-btn g-btn-primary">Search businesses<x-growya.icon
                        name="arrow-right" /></button>
            </form>
        </section>
        @if ($errors->any())
            <div class="g-alert g-tone-rose" role="alert">{{ $errors->first() }}</div>
        @endif
        <nav class="g-filter-chips" aria-label="Business categories"><a
                class="g-filter-chip {{ !request('category') ? 'is-current' : '' }}"
                href="{{ route('public.marketplace.index', array_filter(['q' => request('q'), 'city' => request('city'), 'sort' => request('sort')])) }}"
                @if (!request('category')) aria-current="page" @endif>All businesses</a>
            @foreach ($categories as $category)
                <a class="g-filter-chip {{ request('category') === $category->slug ? 'is-current' : '' }}"
                    href="{{ route('public.marketplace.index', array_filter(['category' => $category->slug, 'q' => request('q'), 'city' => request('city'), 'sort' => request('sort')])) }}"
                    @if (request('category') === $category->slug) aria-current="page" @endif>{{ $category->name }}</a>
            @endforeach
        </nav>
        <div class="g-results-heading">
            <div>
                <h2>Explore businesses</h2>
                <p>{{ number_format($businesses->total()) }} approved
                    {{ $businesses->total() === 1 ? 'business' : 'businesses' }}{{ request('city') ? ' in ' . request('city') : ' to discover' }}
                </p>
            </div>
            <form method="GET"><label for="market-sort" class="sr-only">Sort businesses</label>
                @foreach (['q', 'city', 'category'] as $filter)
                    @if (request($filter))
                        <input type="hidden" name="{{ $filter }}" value="{{ request($filter) }}">
                    @endif
                @endforeach
                <select id="market-sort" name="sort" onchange="this.form.submit()">
                    <option value="newest" @selected(request('sort', 'newest') === 'newest')>Newest first</option>
                    <option value="popular" @selected(request('sort') === 'popular')>Most viewed</option>
                    <option value="name" @selected(request('sort') === 'name')>Name A–Z</option>
                </select><noscript><button class="g-btn g-btn-secondary" type="submit">Apply</button></noscript>
            </form>
        </div>
        <div class="g-business-grid">
            @forelse($businesses as $business)
                @include('public.partials.business-card', ['business' => $business])@empty
                <div class="g-panel" style="grid-column:1/-1"><x-growya.empty title="No matches this time"
                        description="Try a different name, category or city to find local businesses." icon="search"
                        :href="route('public.marketplace.index')" action="Clear filters" /></div>
            @endforelse
        </div>
        <div class="g-pagination">{{ $businesses->withQueryString()->links() }}</div>
    </main>
    @include('public.partials.site-footer')
</body>

</html>
