<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <meta name="robots" content="noindex,follow">
    <title>Search Businesses, Products & Services | Growya</title>
  
@include('public.partials.assets')
</head>

<body class="g-public">
    @include('public.partials.site-header')

    @php
        // Image URL current domain se banta hai (APP_URL ka issue nahi aata).
        $storageUrl = static fn($path) => !$path
            ? null
            : (\Illuminate\Support\Str::startsWith($path, ['http://', 'https://'])
                ? $path
                : asset('storage/' . ltrim($path, '/')));

        $tabParams = array_filter(
            ['q' => $q, 'city_id' => $cityId, 'min_price' => $min, 'max_price' => $max],
            fn($v) => $v !== null && $v !== '',
        );
        $singular = ucfirst(substr($type, 0, -1));
    @endphp

    <main id="main-content" class="mx-auto max-w-7xl px-4 py-8 lg:px-8">

        {{-- Hero + search --}}
        <section class="rounded-3xl bg-gradient-to-r from-emerald-700 to-teal-700 p-7 text-white sm:p-10">
            <p class="text-xs font-bold uppercase tracking-widest text-emerald-100">Growya Marketplace</p>
            <h1 class="mt-2 text-3xl font-black sm:text-4xl">Search local businesses, products & services</h1>
            <p class="mt-3 max-w-2xl text-sm text-emerald-50">
                Discover real listings from active, approved businesses.
            </p>

            <form method="GET" action="{{ route('public.search.index') }}" class="relative mt-6 flex flex-wrap gap-2"
                id="global-search-form" autocomplete="off">
                <input type="hidden" name="type" value="{{ $type }}">

                <div class="relative min-w-56 flex-1">
                    <label class="sr-only" for="search-query">Search</label>
                    <input id="search-query" name="q" type="search" maxlength="120" value="{{ $q }}"
                        placeholder="Search business, product or service..."
                        class="w-full rounded-xl border border-emerald-100 px-4 py-3 text-slate-900 outline-emerald-400"
                        aria-autocomplete="list" aria-expanded="false" aria-controls="search-suggestions">

                    <div id="search-suggestions" role="listbox"
                        class="absolute left-0 right-0 top-full z-30 mt-2 hidden max-h-96 overflow-y-auto rounded-xl border border-slate-200 bg-white text-left shadow-xl">
                    </div>
                </div>

                <button class="rounded-xl bg-slate-950 px-7 py-3 font-bold text-white">Search</button>
            </form>
        </section>

        @if ($errors->any())
            <div class="mt-5 rounded-xl border border-rose-200 bg-rose-50 p-4 text-sm text-rose-700">
                {{ $errors->first() }}
            </div>
        @endif

        {{-- Type tabs --}}
        <nav class="mt-7 flex flex-wrap gap-2" aria-label="Search result types">
            @foreach (['businesses' => 'Businesses', 'products' => 'Products', 'services' => 'Services'] as $key => $label)
                <a href="{{ route('public.search.index', $tabParams + ['type' => $key]) }}"
                    class="rounded-xl border px-5 py-2.5 text-sm font-bold transition {{ $type === $key ? 'border-emerald-700 bg-emerald-700 text-white' : 'border-slate-200 bg-white text-slate-700 hover:border-emerald-300' }}">
                    {{ $label }}
                </a>
            @endforeach
        </nav>

        {{-- Filters --}}
        <form method="GET" action="{{ route('public.search.index') }}"
            class="mt-5 grid gap-3 rounded-2xl border border-slate-200 bg-white p-4 sm:grid-cols-2 {{ $type !== 'businesses' ? 'lg:grid-cols-5' : 'lg:grid-cols-3' }}">
            <input type="hidden" name="q" value="{{ $q }}">
            <input type="hidden" name="type" value="{{ $type }}">

            <label class="text-sm font-semibold">Category
                <select name="category_id"
                    class="mt-1 block w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 font-normal">
                    <option value="">All Categories</option>
                    @foreach ($categories as $category)
                        <option value="{{ $category->id }}" @selected((string) $categoryId === (string) $category->id)>
                            {{ $category->name }}
                        </option>
                    @endforeach
                </select>
            </label>

            <label class="text-sm font-semibold">City
                <select name="city_id"
                    class="mt-1 block w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 font-normal">
                    <option value="">All Cities</option>
                    @foreach ($cities as $city)
                        <option value="{{ $city->id }}" @selected((string) $cityId === (string) $city->id)>
                            {{ $city->name }}
                        </option>
                    @endforeach
                </select>
            </label>

            @if ($type !== 'businesses')
                <label class="text-sm font-semibold">Min Price (₹)
                    <input name="min_price" type="number" min="0" step="0.01" value="{{ $min }}"
                        class="mt-1 block w-full rounded-xl border border-slate-200 px-3 py-2.5 font-normal">
                </label>

                <label class="text-sm font-semibold">Max Price (₹)
                    <input name="max_price" type="number" min="0" step="0.01" value="{{ $max }}"
                        class="mt-1 block w-full rounded-xl border border-slate-200 px-3 py-2.5 font-normal">
                </label>
            @endif

            <div class="flex items-end gap-2">
                <button class="rounded-xl bg-slate-900 px-5 py-2.5 text-sm font-bold text-white">Filter</button>
                <a href="{{ route('public.search.index', ['q' => $q, 'type' => $type]) }}"
                    class="rounded-xl border border-slate-200 px-4 py-2.5 text-sm">Reset</a>
            </div>
        </form>

        {{-- Results heading --}}
        <div class="mt-7 flex flex-wrap items-center justify-between gap-3">
            <h2 class="text-xl font-black">
                {{ ucfirst($type) }}
                <span class="text-sm font-normal text-slate-500">({{ $results->total() }} results)</span>
            </h2>

            @if ($q !== '')
                <p class="text-sm text-slate-500">Showing results for <b class="text-slate-800">"{{ $q }}"</b></p>
            @endif
        </div>

        {{-- Results --}}
        <div class="mt-5 grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
            @forelse($results as $item)
                @php
                    $business = $type === 'businesses' ? $item : $item->business;
                    $path = $type === 'businesses' ? ($item->cover ?: $item->logo) : $item->featured_image;
                    $photo = $storageUrl($path);

                    $link = match ($type) {
                        'products' => route('public.business.product', [$business->slug, $item->slug]),
                        'services' => route('public.business.service', [$business->slug, $item->slug]),
                        default => route('public.business.home', $business->slug),
                    };

                    $price = $type === 'businesses' ? null : $item->offer_price ?? $item->price;
                    $hasOffer =
                        $type !== 'businesses' &&
                        $item->offer_price !== null &&
                        (float) $item->offer_price < (float) $item->price;
                @endphp

                <a href="{{ $link }}"
                    class="group overflow-hidden rounded-2xl border border-slate-200 bg-white transition hover:shadow-lg">

                    <div class="relative aspect-video bg-slate-100">
                        @if ($photo)
                            <img src="{{ $photo }}" alt="{{ $item->name }}" loading="lazy"
                                class="h-full w-full {{ $type === 'products' ? 'object-contain p-2' : 'object-cover' }} transition group-hover:scale-[1.02]">
                        @else
                            <div
                                class="flex h-full items-center justify-center text-3xl font-black text-slate-300">
                                {{ mb_strtoupper(mb_substr($item->name, 0, 1)) }}
                            </div>
                        @endif

                        <span
                            class="absolute left-3 top-3 rounded-full bg-white/95 px-3 py-1 text-xs font-bold text-emerald-700 shadow-sm">
                            {{ $type === 'businesses' ? '✓ Verified' : $singular }}
                        </span>
                    </div>

                    <div class="space-y-2 p-5">
                        <h3 class="line-clamp-1 text-lg font-bold">{{ $item->name }}</h3>

                        @if ($type === 'businesses')
                            <p class="text-sm text-slate-500">
                                {{ $item->category?->name ?? 'Business' }}
                                @if ($item->city?->name)
                                    • {{ $item->city->name }}
                                @endif
                            </p>
                        @else
                            <p class="text-sm text-slate-500">
                                By {{ $business->name }}
                                @if ($item->category?->name)
                                    • {{ $item->category->name }}
                                @endif
                            </p>

                            <div class="flex items-baseline gap-2">
                                <p class="font-black text-emerald-700">
                                    {{ $price !== null ? '₹' . number_format((float) $price, 2) : 'Price on request' }}
                                </p>
                                @if ($hasOffer)
                                    <p class="text-sm text-slate-400 line-through">
                                        ₹{{ number_format((float) $item->price, 2) }}
                                    </p>
                                @endif
                            </div>
                        @endif

                        <span class="inline-block pt-1 text-sm font-bold text-emerald-700">View Details →</span>
                    </div>
                </a>
            @empty
                <div
                    class="rounded-2xl border border-dashed border-slate-300 bg-white p-12 text-center sm:col-span-2 lg:col-span-3">
                    <p class="text-lg font-bold text-slate-700">No matching {{ $type }} found</p>
                    <p class="mt-2 text-sm text-slate-500">
                        Try a different keyword, or remove some filters.
                    </p>
                    <a href="{{ route('public.search.index', ['type' => $type]) }}"
                        class="mt-5 inline-block rounded-xl bg-emerald-600 px-5 py-2.5 text-sm font-bold text-white">
                        Clear search
                    </a>
                </div>
            @endforelse
        </div>

        <div class="mt-8">{{ $results->withQueryString()->links() }}</div>
    </main>

    @include('public.partials.site-footer')

    <script>
        (() => {
            const form = document.getElementById('global-search-form');
            const input = document.getElementById('search-query');
            const box = document.getElementById('search-suggestions');
            const endpoint = @json(route('public.search.suggest'));

            let controller = null;
            let debounce = null;
            let requestId = 0;

            const open = () => {
                box.classList.remove('hidden');
                input.setAttribute('aria-expanded', 'true');
            };

            const hide = () => {
                box.classList.add('hidden');
                box.replaceChildren();
                input.setAttribute('aria-expanded', 'false');
            };

            // Ek message row (Searching / Not found)
            const showMessage = (title, subtitle) => {
                box.replaceChildren();

                const wrap = document.createElement('div');
                wrap.className = 'px-4 py-5 text-center';

                const t = document.createElement('p');
                t.className = 'text-sm font-semibold text-slate-700';
                t.textContent = title;
                wrap.append(t);

                if (subtitle) {
                    const s = document.createElement('p');
                    s.className = 'mt-1 text-xs text-slate-500';
                    s.textContent = subtitle;
                    wrap.append(s);
                }

                box.append(wrap);
                open();
            };

            const typeBadgeClass = {
                Business: 'bg-emerald-50 text-emerald-700',
                Product: 'bg-blue-50 text-blue-700',
                Service: 'bg-amber-50 text-amber-700',
            };

            const render = (items, value) => {
                box.replaceChildren();

                // Not found
                if (!items.length) {
                    showMessage(
                        'No results found for "' + value + '"',
                        'Try a different spelling or a shorter keyword.'
                    );
                    return;
                }

                items.forEach(item => {
                    const a = document.createElement('a');
                    a.href = item.url;
                    a.setAttribute('role', 'option');
                    a.className =
                        'flex items-center justify-between gap-3 border-b border-slate-100 px-4 py-3 text-sm text-slate-800 hover:bg-emerald-50';

                    const label = document.createElement('strong');
                    label.className = 'truncate';
                    label.textContent = item.label;

                    const badge = document.createElement('span');
                    badge.className = 'shrink-0 rounded-full px-2.5 py-0.5 text-xs font-semibold ' +
                        (typeBadgeClass[item.type] || 'bg-slate-100 text-slate-600');
                    badge.textContent = item.type;

                    a.append(label, badge);
                    box.append(a);
                });

                // "See all results" link
                const all = document.createElement('button');
                all.type = 'submit';
                all.className =
                    'block w-full px-4 py-3 text-center text-sm font-bold text-emerald-700 hover:bg-emerald-50';
                all.textContent = 'See all results for "' + value + '" →';
                box.append(all);

                open();
            };

            input.addEventListener('input', () => {
                clearTimeout(debounce);
                if (controller) controller.abort();

                const value = input.value.trim();

                if (value.length < 2) {
                    hide();
                    return;
                }

                debounce = setTimeout(async () => {
                    const currentId = ++requestId;
                    controller = new AbortController();

                    showMessage('Searching...');

                    try {
                        const response = await fetch(endpoint + '?q=' + encodeURIComponent(value), {
                            signal: controller.signal,
                            headers: {
                                Accept: 'application/json'
                            }
                        });

                        // Purani request ka jawab ignore karo
                        if (currentId !== requestId || input.value.trim() !== value) return;

                        if (!response.ok) {
                            showMessage('Search is unavailable right now', 'Please try again in a moment.');
                            return;
                        }

                        const data = await response.json();
                        render(data.items || [], value);
                    } catch (e) {
                        if (e.name !== 'AbortError') {
                            showMessage('Something went wrong', 'Please try again.');
                        }
                    }
                }, 250);
            });

            input.addEventListener('keydown', e => {
                if (e.key === 'Escape') hide();
            });

            document.addEventListener('click', e => {
                if (!form.contains(e.target)) hide();
            });
        })();
    </script>
</body>

</html>