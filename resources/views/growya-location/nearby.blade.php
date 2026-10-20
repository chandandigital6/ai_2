@php
    // Map data pehle yahan banta hai, taaki script ke andar @json mein nested brackets na likhne padein.
    $mapOrigin = $geo ? [(float) request('lat'), (float) request('lng')] : null;

    $mapMarkers = $geo
        ? $businesses
            ->getCollection()
            ->filter(fn($b) => $b->latitude !== null && $b->longitude !== null)
            ->map(function ($b) {
                return [
                    'name' => $b->name,
                    'lat' => (float) $b->latitude,
                    'lng' => (float) $b->longitude,
                    'url' => route('public.business.home', $b->slug),
                ];
            })
            ->values()
            ->all()
        : [];

    $total = $businesses->total();
@endphp
<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Nearby businesses | Growya</title>
    @include('public.partials.assets')
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" crossorigin="" />
    <style>
        .near-wrap {
            padding: 28px 0 65px
        }

        .near-panel {
            background: white;
            border: 1px solid #e3e7e5;
            border-radius: 18px;
            padding: 20px;
            margin-bottom: 18px
        }

        .near-error {
            border-color: #f1b5b5;
            background: #fff4f4;
            color: #8a1f1f
        }

        .near-fields {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(175px, 1fr));
            gap: 12px
        }

        .near-fields label {
            display: grid;
            gap: 6px;
            font-size: 13px;
            font-weight: 600
        }

        .near-fields input,
        .near-fields select {
            height: 42px;
            border: 1px solid #ccd5d1;
            border-radius: 10px;
            padding: 8px;
            width: 100%;
            background: white;
            color: #142d26
        }

        .near-fields input:focus,
        .near-fields select:focus {
            outline: 2px solid #b7e3d0;
            border-color: #117750
        }

        #near-map {
            height: 350px;
            max-width: 100%;
            border-radius: 14px;
            margin-bottom: 18px;
            z-index: 0
        }

        .near-card {
            border: 1px solid #e1e8e4;
            padding: 16px;
            border-radius: 15px;
            background: white;
            display: flex;
            flex-direction: column;
            gap: 6px
        }

        .near-card h3 {
            margin: 0
        }

        .near-card p {
            margin: 0
        }

        .near-meta {
            font-size: 13px;
            color: #586862
        }

        .near-distance {
            display: inline-block;
            width: fit-content;
            background: #e6f6ef;
            color: #0d5c3d;
            font-size: 12px;
            font-weight: 700;
            padding: 4px 10px;
            border-radius: 999px
        }

        .near-card .near-btn {
            margin-top: auto;
            align-self: flex-start
        }

        .near-results {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 14px
        }

        .near-actions {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-top: 16px;
            flex-wrap: wrap
        }

        .near-note {
            font-size: 13px;
            color: #586862;
            margin: 9px 0
        }

        .near-btn {
            border: 0;
            background: #117750;
            color: white;
            padding: 11px 15px;
            border-radius: 10px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block
        }

        .near-btn:hover {
            background: #0d5c3d
        }

        .near-btn-outline {
            background: white;
            color: #117750;
            border: 1px solid #117750
        }

        .near-btn-outline:hover {
            background: #e6f6ef
        }
    </style>
</head>

<body class="g-public">
    @include('public.partials.site-header')
    <main class="g-container near-wrap" id="main-content">
        <h1>Discover nearby businesses</h1>
        <p class="near-note">Find verified marketplace listings by city or by distance from a location you choose.</p>

        @if ($errors->any())
            <div class="near-panel near-error" role="alert">{{ $errors->first() }}</div>
        @endif

        <form class="near-panel" method="GET" action="{{ route('growya.nearby.index') }}" id="near-search">
            <div class="near-fields">
                <label>Search
                    <input name="q" maxlength="120" value="{{ request('q') }}" placeholder="Business or category">
                </label>

                <label>Category
                    <select name="category">
                        <option value="">All categories</option>
                        @foreach ($categories as $c)
                            <option value="{{ $c->slug }}" @selected(request('category') === $c->slug)>{{ $c->name }}</option>
                        @endforeach
                    </select>
                </label>

                <label>State
                    <select name="state_id" id="near-state">
                        <option value="">All states</option>
                        @foreach ($states as $state)
                            <option value="{{ $state->id }}" @selected((string) request('state_id') === (string) $state->id)>{{ $state->name }}</option>
                        @endforeach
                    </select>
                </label>

                <label>City
                    <select name="city_id" id="near-city">
                        <option value="">All cities</option>
                        @foreach ($cities as $city)
                            <option data-state="{{ $city->state_id }}" value="{{ $city->id }}"
                                @selected((string) request('city_id') === (string) $city->id)>{{ $city->name }}</option>
                        @endforeach
                    </select>
                </label>

                <label>Latitude
                    <input type="number" step="any" min="-90" max="90" id="near-lat" name="lat"
                        value="{{ request('lat') }}" placeholder="e.g. 26.45">
                </label>

                <label>Longitude
                    <input type="number" step="any" min="-180" max="180" id="near-lng" name="lng"
                        value="{{ request('lng') }}" placeholder="e.g. 80.33">
                </label>

                <label>Radius
                    <select name="radius">
                        @foreach ([5, 10, 25, 50] as $r)
                            <option value="{{ $r }}" @selected((int) request('radius', 25) === $r)>{{ $r }} km</option>
                        @endforeach
                    </select>
                </label>

                <label>Sort
                    <select name="sort">
                        @foreach (['nearest' => 'Nearest', 'newest' => 'Newest', 'popular' => 'Popular'] as $value => $label)
                            <option value="{{ $value }}" @selected(request('sort', 'nearest') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </label>
            </div>

            <div class="near-actions">
                <button class="near-btn" type="submit">Search businesses</button>
                <button class="near-btn near-btn-outline" type="button" id="near-locate">Use my location</button>
                <a href="{{ route('growya.nearby.index') }}">Clear filters</a>
            </div>

            <p class="near-note" id="near-message" aria-live="polite">
                Location access optional hai; ye HTTPS ya localhost par chalta hai.
                Coordinates sirf tab bheje jaate hain jab aap search chalate ho.
            </p>
        </form>

        @if ($geo)
            <div id="near-map" role="region" aria-label="Map of businesses in the search results"></div>
            <p class="near-note">Map sirf is page ke businesses dikhata hai. Distance seedhi line ka andaaza hai,
                driving distance nahi.</p>
        @endif

        <h2>{{ number_format($total) }} {{ $total === 1 ? 'business' : 'businesses' }} found</h2>

        <div class="near-results" style="margin:15px 0 20px">
            @forelse ($businesses as $business)
                <article class="near-card">
                    <h3><a href="{{ route('public.business.home', $business->slug) }}">{{ $business->name }}</a></h3>

                    <p class="near-meta">
                        {{ $business->category?->name ?? 'Business' }} ·
                        {{ $business->city?->name ?? 'City unavailable' }}{{ $business->state?->name ? ', ' . $business->state->name : '' }}
                    </p>

                    @if ($geo && $business->distance_km !== null)
                        <span class="near-distance">{{ number_format((float) $business->distance_km, 1) }} km away</span>
                    @endif

                    @if ($business->address)
                        <p class="near-meta">{{ \Illuminate\Support\Str::limit($business->address, 130) }}</p>
                    @endif

                    <a class="near-btn" href="{{ route('public.business.home', $business->slug) }}">View showroom</a>
                </article>
            @empty
                <div class="near-panel">
                    Koi matching business nahi mila. Doosri city try karo, radius badhao ya koi filter hata do.
                </div>
            @endforelse
        </div>

        {{ $businesses->links() }}
    </main>

    <script>
        (function() {
            const state = document.getElementById('near-state'),
                city = document.getElementById('near-city');
            const options = [...city.options].slice(1).map(o => ({
                value: o.value,
                label: o.textContent,
                state: o.dataset.state
            }));

            state.addEventListener('change', () => {
                const previous = city.value;
                city.length = 1;
                for (const o of options) {
                    if (!state.value || o.state === state.value) {
                        city.add(new Option(o.label, o.value));
                    }
                }
                if ([...city.options].some(o => o.value === previous)) city.value = previous;
            });

            document.getElementById('near-locate').addEventListener('click', () => {
                const msg = document.getElementById('near-message');
                if (!navigator.geolocation) {
                    msg.textContent = 'Is browser mein location support nahi hai.';
                    return;
                }
                msg.textContent = 'Aapki location maangi ja rahi hai…';
                navigator.geolocation.getCurrentPosition(p => {
                    document.getElementById('near-lat').value = p.coords.latitude.toFixed(6);
                    document.getElementById('near-lng').value = p.coords.longitude.toFixed(6);
                    msg.textContent = 'Location mil gayi. Ab "Search businesses" dabao.';
                }, () => {
                    msg.textContent = 'Location access nahi mila. Coordinates haath se bhi daal sakte ho.';
                }, {
                    enableHighAccuracy: false,
                    timeout: 12000
                });
            });
        })();
    </script>

    @if ($geo)
        <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" crossorigin=""></script>
        <script>
            (function() {
                if (typeof L === 'undefined') return;

                const origin = @json($mapOrigin);
                const markers = @json($mapMarkers);

                const map = L.map('near-map').setView(origin, 11);
                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    maxZoom: 18,
                    attribution: '© OpenStreetMap contributors'
                }).addTo(map);

                const pins = [L.marker(origin).addTo(map).bindTooltip('Search centre')];

                for (const b of markers) {
                    if (!Number.isFinite(b.lat) || !Number.isFinite(b.lng)) continue;

                    const pin = L.marker([b.lat, b.lng]).addTo(map);
                    const a = document.createElement('a');
                    a.href = b.url;
                    a.textContent = b.name;
                    pin.bindPopup(a);
                    pins.push(pin);
                }

                if (pins.length > 1) map.fitBounds(L.featureGroup(pins).getBounds().pad(.2));
            })();
        </script>
    @endif
</body>

</html>
