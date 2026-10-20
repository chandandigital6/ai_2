@extends('public.businesses.layout')

@section('title', $business->name . ' | Verified Growya Business')

@section('content')

    @php
        // Image URL: current domain se banta hai, to APP_URL ka issue nahi aata.
        $storageUrl = static fn($path) => !$path
            ? null
            : (\Illuminate\Support\Str::startsWith($path, ['http://', 'https://'])
                ? $path
                : asset('storage/' . ltrim($path, '/')));

        $cover = $storageUrl($business->cover);
        $logo = $storageUrl($business->logo);

        $phone = preg_replace('/[^0-9+]/', '', (string) $business->phone);
        $wa = preg_replace('/\D/', '', (string) $business->whatsapp);

        if (strlen($wa) === 10) {
            $wa = '91' . $wa;
        }

        $dayNames = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
        $days = $business->hours->keyBy('day_of_week');

        // Aaj ka time aur "Open Now" status.
        $todayHour = $days[now()->dayOfWeek] ?? null;
        $isOpen = null;

        if ($todayHour) {
            if ($todayHour->is_closed) {
                $isOpen = false;
            } elseif ($todayHour->open_time && $todayHour->close_time) {
                $nowTime = now()->format('H:i:s');
                $open = $todayHour->open_time;
                $close = $todayHour->close_time;

                $isOpen =
                    $close >= $open ? $nowTime >= $open && $nowTime <= $close : $nowTime >= $open || $nowTime <= $close; // raat tak khulne wali shop
            }
        }

        $followers = (int) $business->followers_count;
    @endphp

    

    {{-- Hero: cover + logo + name --}}
    <section class="relative bg-[#173f31]">
        <div class="h-64 w-full bg-gradient-to-br from-emerald-900 via-emerald-950 to-slate-950 md:h-80">
            @if ($cover)
                <img src="{{ $cover }}" alt="{{ $business->name }} cover" class="h-full w-full object-cover">
            @endif
        </div>

        <div class="absolute inset-0 bg-gradient-to-t from-slate-950/80 via-transparent to-transparent"></div>

        <div class="absolute inset-x-0 bottom-0 mx-auto flex max-w-7xl items-end gap-4 px-4 pb-6 lg:px-8">
            <div
                class="grid h-20 w-20 shrink-0 place-items-center overflow-hidden rounded-3xl border-4 border-white bg-emerald-100 text-2xl font-black text-emerald-900 shadow-lg md:h-24 md:w-24">
                @if ($logo)
                    <img src="{{ $logo }}" alt="{{ $business->name }} logo" class="h-full w-full object-cover">
                @else
                    {{ mb_strtoupper(mb_substr($business->name, 0, 2)) }}
                @endif
            </div>

            <div class="min-w-0 text-white">
                <p class="text-xs font-extrabold tracking-wider text-emerald-300">✓ VERIFIED BUSINESS</p>
                <h1 class="mt-1 text-2xl font-black md:text-4xl">{{ $business->name }}</h1>

                <p class="mt-1 text-sm text-white/85">
                    {{ $business->category?->name ?? 'Local Business' }}
                    @if ($business->city)
                        · {{ $business->city->name }}
                    @endif
                    @if ($business->state)
                        , {{ $business->state->name }}
                    @endif
                </p>

                <p
                    class="mt-2 inline-flex items-center gap-1.5 rounded-full bg-white/15 px-3 py-1 text-xs font-bold backdrop-blur">
                    <x-growya.icon name="users" style="width:13px;height:13px" /> {{ number_format($followers) }} {{ $followers === 1 ? 'follower' : 'followers' }}
                </p>


            </div>
        </div>
    </section>

    {{-- Flash messages --}}
    @if (session('success'))
        <div class="mx-auto mt-4 max-w-7xl px-4 lg:px-8">
            <div class="rounded-xl bg-emerald-50 p-3 text-sm font-semibold text-emerald-800">
                {{ session('success') }}
            </div>
        </div>
    @endif

    @if ($errors->any())
        <div class="mx-auto mt-4 max-w-7xl px-4 lg:px-8">
            <div class="rounded-xl bg-rose-50 p-3 text-sm font-semibold text-rose-700">
                {{ $errors->first() }}
            </div>
        </div>
    @endif

    {{-- Action bar --}}
    <section class="border-b bg-white">
        <div class="mx-auto flex max-w-7xl flex-wrap items-center justify-between gap-4 px-4 py-4 lg:px-8">
            <div class="flex flex-wrap items-center gap-2">
                @include('public.businesses.partials.follow-button')


                <a href="{{ route('public.business.reviews.index', $business->slug) }}"
                    class="rounded-xl bg-white px-5 py-3 font-semibold text-emerald-700">
                    Customer Reviews
                </a>

                <a href="{{ route('public.business.offers', $business->slug) }}"
                    class="rounded-xl bg-emerald-700 px-5 py-3 font-semibold text-white">
                    Special Offers
                </a>

                @if ($phone)
                    <a href="tel:{{ $phone }}"
                        class="rounded-xl border border-slate-200 px-4 py-2 text-sm font-bold text-slate-800 hover:bg-slate-50"><x-growya.icon name="globe" />
                        Call</a>
                @endif

                @if ($wa)
                    <a href="https://wa.me/{{ $wa }}" rel="noopener noreferrer" target="_blank"
                        class="rounded-xl border border-emerald-200 px-4 py-2 text-sm font-bold text-emerald-700 hover:bg-emerald-50">WhatsApp</a>
                @endif

                <a href="{{ route('public.business.contact', $business->slug) }}"
                    class="rounded-xl bg-emerald-600 px-4 py-2 text-sm font-bold text-white hover:bg-emerald-700">Contact
                    Business</a>
            </div>

            <button type="button"
                onclick="if(navigator.share){navigator.share({title:@js($business->name),url:location.href})}else if(navigator.clipboard){navigator.clipboard.writeText(location.href).then(()=>alert('Link copied'))}"
                class="rounded-xl border border-slate-200 px-4 py-2 text-sm font-bold hover:bg-slate-50">Share ↗</button>
        </div>
    </section>

    <div class="mx-auto grid max-w-7xl gap-8 px-4 py-10 lg:grid-cols-[minmax(0,1fr)_320px] lg:px-8">

        <div class="min-w-0">

            <nav class="flex gap-6 overflow-x-auto border-b text-sm font-bold" aria-label="Showroom sections">
                <a href="#products" class="border-b-2 border-emerald-600 pb-3 text-emerald-700">Products</a>
                <a href="#services" class="pb-3 hover:text-emerald-600">Services</a>
                <a href="#gallery" class="pb-3 hover:text-emerald-600">Gallery</a>
                <a href="#about" class="pb-3 hover:text-emerald-600">About</a>
            </nav>

            {{-- Products --}}
            <section id="products" class="scroll-mt-24">
                <div class="mt-8 flex items-center justify-between gap-3">
                    <h2 class="text-2xl font-black">Featured Products</h2>
                    <a href="{{ route('public.business.products', $business->slug) }}"
                        class="text-sm font-bold text-emerald-700">View all →</a>
                </div>

                <div class="mt-5 grid grid-cols-2 gap-4 md:grid-cols-3">
                    @forelse($products as $product)
                        @php
                            $pimage = $storageUrl($product->featured_image);
                            $price = $product->offer_price ?? $product->price;
                        @endphp

                        <a href="{{ route('public.business.product', [$business->slug, $product->slug]) }}"
                            class="g-store-card card overflow-hidden rounded-2xl border bg-white">
                            <div class="aspect-square bg-slate-100">
                                @if ($pimage)
                                    <img src="{{ $pimage }}" alt="{{ $product->name }}" loading="lazy"
                                        class="h-full w-full object-contain">
                                @else
                                    <div class="grid h-full place-items-center text-slate-400">No product image</div>
                                @endif
                            </div>

                            <div class="p-4">
                                <p class="text-xs font-bold uppercase text-slate-400">
                                    {{ $product->category?->name ?? 'PRODUCT' }}
                                </p>
                                <h3 class="mt-1 font-black">{{ $product->name }}</h3>
                                <p class="mt-2 font-black text-emerald-700">
                                    {{ $price !== null ? '₹' . number_format((float) $price, 2) : 'Ask for price' }}
                                </p>
                            </div>
                        </a>
                    @empty
                        <p class="col-span-full rounded-xl bg-white p-6 text-sm text-slate-500">
                            No public products added yet.
                        </p>
                    @endforelse
                </div>
            </section>

            {{-- Services --}}
            <section id="services" class="scroll-mt-24">
                <div class="mt-12 flex items-center justify-between gap-3">
                    <h2 class="text-2xl font-black">Our Services</h2>
                    <a href="{{ route('public.business.services', $business->slug) }}"
                        class="text-sm font-bold text-emerald-700">View all →</a>
                </div>

                <div class="mt-5 grid gap-4 sm:grid-cols-2">
                    @forelse($services as $service)
                        @php
                            $simage = $storageUrl($service->featured_image);
                            $sprice = $service->offer_price ?? $service->price;
                        @endphp

                        <div class="g-store-card card overflow-hidden rounded-2xl border bg-white">
                            <div class="aspect-video bg-slate-100">
                                @if ($simage)
                                    <img src="{{ $simage }}" alt="{{ $service->name }}" loading="lazy"
                                        class="h-full w-full object-cover">
                                @else
                                    <div class="grid h-full place-items-center text-slate-400">Service photo</div>
                                @endif
                            </div>

                            <div class="p-4">
                                <h3 class="font-black">{{ $service->name }}</h3>
                                <p class="mt-2 line-clamp-2 text-sm text-slate-500">{{ $service->description }}</p>
                                <p class="mt-2 font-black text-emerald-700">
                                    {{ $sprice !== null ? '₹' . number_format((float) $sprice, 2) : 'Price on request' }}
                                </p>

                                <div class="mt-4 flex gap-2">
                                    <a href="{{ route('public.business.service', [$business->slug, $service->slug]) }}"
                                        class="rounded-xl border px-3 py-2 text-sm font-bold">Details</a>
                                    <a href="{{ route('public.services.enquiries.create', [$business, $service]) }}"
                                        class="rounded-xl bg-emerald-600 px-3 py-2 text-sm font-bold text-white">Enquire</a>
                                </div>
                            </div>
                        </div>
                    @empty
                        <p class="col-span-full rounded-xl bg-white p-6 text-sm text-slate-500">
                            No public services added yet.
                        </p>
                    @endforelse
                </div>
            </section>

            {{-- Gallery --}}
            <section id="gallery" class="scroll-mt-24">
                <div class="mt-12 flex items-center justify-between gap-3">
                    <h2 class="text-2xl font-black">Business Gallery</h2>
                    <a href="{{ route('public.business.gallery', $business->slug) }}"
                        class="text-sm font-bold text-emerald-700">View all →</a>
                </div>

                <div class="mt-5 grid grid-cols-2 gap-4 md:grid-cols-3">
                    @forelse($gallery as $media)
                        <div class="overflow-hidden rounded-2xl border bg-white">
                            @if ($media->is_video)
                                <video src="{{ $media->media_url }}" controls preload="metadata" playsinline
                                    class="aspect-video w-full bg-black object-contain"></video>
                            @else
                                <a href="{{ $media->media_url }}" target="_blank" rel="noopener noreferrer">
                                    <img src="{{ $media->media_url }}" alt="{{ $media->title ?: $business->name }}"
                                        loading="lazy" class="aspect-video w-full object-cover">
                                </a>
                            @endif

                            @if ($media->title)
                                <p class="px-3 py-2 text-sm font-bold">{{ $media->title }}</p>
                            @endif
                        </div>
                    @empty
                        <p class="col-span-full rounded-xl bg-white p-6 text-sm text-slate-500">
                            Gallery photos coming soon.
                        </p>
                    @endforelse
                </div>
            </section>

            {{-- About --}}
            <section id="about" class="mt-12 scroll-mt-24 rounded-3xl border bg-white p-6">
                <h2 class="text-2xl font-black">About {{ $business->name }}</h2>
                <p class="mt-4 whitespace-pre-line leading-7 text-slate-600">
                    {{ $business->description ?: 'Welcome to our Growya business showroom.' }}
                </p>
            </section>
        </div>

        {{-- Sidebar --}}
        <aside class="space-y-5">

            <section class="rounded-3xl border bg-white p-5">
                <h2 class="font-black">Business Information</h2>

                <div class="mt-4 space-y-4 text-sm">
                    @if ($business->address)
                        <p class="flex gap-3">
                            <x-growya.icon name="pin" class="text-emerald-700" />
                            <span>
                                {{ $business->address }}
                                @if ($business->pincode)
                                    - {{ $business->pincode }}
                                @endif
                            </span>
                        </p>
                    @endif

                    <div class="flex gap-3">
                        <x-growya.icon name="clock" class="text-emerald-700" />
                        <div>
                            <p class="font-bold">
                                @if ($isOpen === true)
                                    <span class="text-emerald-700">Open Now</span>
                                @elseif ($isOpen === false)
                                    <span class="text-rose-600">Closed Now</span>
                                @else
                                    <span class="text-slate-500">Hours not listed</span>
                                @endif
                            </p>

                            @if ($todayHour && !$todayHour->is_closed && $todayHour->open_time && $todayHour->close_time)
                                <p class="mt-1 text-slate-500">
                                    {{ substr($todayHour->open_time, 0, 5) }} –
                                    {{ substr($todayHour->close_time, 0, 5) }}
                                </p>
                            @endif
                        </div>
                    </div>

                    <p class="flex gap-3"><span>✓</span><span>Verified on Growya</span></p>
                </div>
            </section>

            @if ($business->hours->isNotEmpty())
                <section class="rounded-3xl border bg-white p-5">
                    <h2 class="font-black">Weekly Opening Hours</h2>

                    <div class="mt-4 space-y-2 text-sm">
                        @foreach ($dayNames as $index => $day)
                            <div class="flex justify-between gap-3">
                                <span
                                    class="{{ now()->dayOfWeek === $index ? 'font-bold text-emerald-700' : 'text-slate-500' }}">
                                    {{ $day }}
                                </span>

                                <span class="text-right">
                                    @if (isset($days[$index]) && !$days[$index]->is_closed && $days[$index]->open_time && $days[$index]->close_time)
                                        {{ substr($days[$index]->open_time, 0, 5) }}–{{ substr($days[$index]->close_time, 0, 5) }}
                                    @else
                                        Closed
                                    @endif
                                </span>
                            </div>
                        @endforeach
                    </div>
                </section>
            @endif

            @if ($business->socialLinks->isNotEmpty())
                <section class="rounded-3xl border bg-white p-5">
                    <h2 class="font-black">Follow Us</h2>

                    <div class="mt-4 flex flex-wrap gap-2">
                        @foreach ($business->socialLinks as $social)
                            @if (filter_var($social->url, FILTER_VALIDATE_URL) &&
                                    in_array(strtolower(parse_url($social->url, PHP_URL_SCHEME) ?? ''), ['http', 'https'], true))
                                <a href="{{ $social->url }}" target="_blank" rel="noopener noreferrer nofollow"
                                    class="rounded-xl border px-3 py-2 text-sm font-bold capitalize hover:text-emerald-700">
                                    {{ $social->platform }}
                                </a>
                            @endif
                        @endforeach
                    </div>
                </section>
            @endif

            <section class="rounded-3xl bg-slate-950 p-5 text-white">
                <p class="text-xs font-bold text-emerald-400">CONTACT THIS BUSINESS</p>
                <h3 class="mt-2 text-xl font-black">Interested in their products or services?</h3>
                <p class="mt-2 text-sm text-slate-400">Reach out directly using the contact details.</p>

                <a href="{{ route('public.business.contact', $business->slug) }}"
                    class="mt-4 block rounded-xl bg-emerald-500 px-4 py-3 text-center font-bold">
                    Contact Now
                </a>
            </section>
        </aside>
    </div>

@endsection
