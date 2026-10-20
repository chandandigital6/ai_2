@extends('public.businesses.layout')

@section('title', $service->name . ' | ' . $business->name)

@section('content')
    @php
        $assetUrl = function (?string $path) {
            if (!$path) {
                return null;
            }

            return \Illuminate\Support\Str::startsWith($path, ['http://', 'https://'])
                ? $path
                : asset('storage/' . ltrim($path, '/'));
        };

        // Photos: featured + gallery
        $photos = collect();

        if ($service->featured_image) {
            $photos->push(['url' => $assetUrl($service->featured_image), 'alt' => $service->name]);
        }

        foreach ($service->images as $image) {
            $url = $assetUrl($image->image);

            if ($url) {
                $photos->push(['url' => $url, 'alt' => $image->alt_text ?: $service->name]);
            }
        }

        $photos = $photos->unique('url')->values();

        // Price
        $basePrice = $service->price !== null ? (float) $service->price : null;
        $offerPrice = $service->offer_price !== null ? (float) $service->offer_price : null;
        $price = $offerPrice ?? $basePrice;
        $discount =
            $offerPrice !== null && $basePrice !== null && $basePrice > $offerPrice && $basePrice > 0
                ? round((($basePrice - $offerPrice) / $basePrice) * 100)
                : null;

        // Contact
        $whatsapp = preg_replace('/\D/', '', $business->whatsapp ?: '');

        if (strlen($whatsapp) === 10) {
            $whatsapp = '91' . $whatsapp;
        }

        if (strlen($whatsapp) < 11 || strlen($whatsapp) > 15) {
            $whatsapp = '';
        }

        $phone = preg_replace('/[^0-9+]/', '', $business->phone ?: '');
        $shareUrl = route('public.business.service', [$business->slug, $service->slug]);
        $sellerMessage = 'Hello, I am interested in ' . $service->name . ' (' . $shareUrl . '). Please share details.';
    @endphp

    <div class="mx-auto max-w-7xl px-4 py-8 lg:px-8">

        {{-- Flash messages --}}
        @if (session('success'))
            <div class="mb-5 rounded-xl bg-emerald-50 p-4 text-sm font-semibold text-emerald-800" role="status">
                {{ session('success') }}
            </div>
        @endif

        @if (session('error') || $errors->any())
            <div class="mb-5 rounded-xl bg-red-50 p-4 text-sm text-red-700" role="alert">
                {{ session('error') }}
                @foreach ($errors->all() as $message)
                    <div>{{ $message }}</div>
                @endforeach
            </div>
        @endif

        {{-- Breadcrumb --}}
        <nav class="mb-7 flex flex-wrap gap-2 text-sm text-slate-500" aria-label="Breadcrumb">
            @if (\Illuminate\Support\Facades\Route::has('public.marketplace.index'))
                <a class="hover:text-emerald-700" href="{{ route('public.marketplace.index') }}">Marketplace</a>
                <span>/</span>
            @endif
            <a class="hover:text-emerald-700"
                href="{{ route('public.business.home', $business->slug) }}">{{ $business->name }}</a>
            <span>/</span>
            <a class="hover:text-emerald-700" href="{{ route('public.business.services', $business->slug) }}">Services</a>
            <span>/</span>
            <span class="font-semibold text-slate-800">{{ $service->name }}</span>
        </nav>

        <div class="grid gap-9 lg:grid-cols-2">

            {{-- Images --}}
            <section class="min-w-0 lg:sticky lg:top-6 lg:self-start" aria-label="Service images">
                <div class="relative overflow-hidden rounded-3xl border border-slate-200 bg-white">
                    @if ($photos->isNotEmpty())
                        <img data-main-service-image src="{{ $photos[0]['url'] }}" alt="{{ $photos[0]['alt'] }}"
                            class="aspect-video w-full object-cover">
                    @else
                        <div class="flex aspect-video items-center justify-center text-sm text-slate-400">
                            No service image available
                        </div>
                    @endif
                </div>

                @if ($photos->count() > 1)
                    <div class="mt-4 grid grid-cols-4 gap-3 sm:grid-cols-5">
                        @foreach ($photos as $photo)
                            <button type="button" data-photo-url="{{ $photo['url'] }}" data-photo-alt="{{ $photo['alt'] }}"
                                aria-label="Show image {{ $loop->iteration }}"
                                class="service-thumb overflow-hidden rounded-xl border-2 {{ $loop->first ? 'border-emerald-600' : 'border-slate-200' }} bg-white">
                                <img src="{{ $photo['url'] }}" alt="{{ $photo['alt'] }}" loading="lazy"
                                    class="aspect-square w-full object-cover">
                            </button>
                        @endforeach
                    </div>
                @endif
            </section>

            {{-- Details --}}
            <section class="min-w-0 space-y-6 py-1">

                <div>
                    <a href="{{ route('public.business.services', $business->slug) }}"
                        class="text-sm font-bold text-emerald-700">← All Services</a>

                    @if ($service->category ?? null)
                        <div class="mt-5 text-xs font-bold uppercase tracking-widest text-slate-400">
                            {{ $service->category->name }}
                        </div>
                    @endif

                    <h1 class="mt-2 text-3xl font-black leading-tight text-slate-950 md:text-4xl">
                        {{ $service->name }}
                    </h1>

                    <div class="mt-5 flex flex-wrap items-end gap-3">
                        @if ($price !== null)
                            <strong class="text-3xl font-black text-slate-950">₹{{ number_format($price, 2) }}</strong>

                            @if ($discount)
                                <span class="pb-1 text-slate-400 line-through">₹{{ number_format($basePrice, 2) }}</span>
                                <span class="mb-1 rounded-full bg-emerald-50 px-3 py-1 text-xs font-bold text-emerald-700">
                                    {{ $discount }}% OFF
                                </span>
                            @endif
                        @else
                            <strong class="text-2xl font-black text-slate-700">Price on Request</strong>
                        @endif

                        @if ($service->duration_minutes)
                            <span class="mb-1 rounded-full bg-slate-100 px-3 py-1 text-xs font-bold text-slate-600">
                                ⏱ {{ $service->duration_minutes }} min
                            </span>
                        @endif
                    </div>

                    @if ($service->description)
                        <p class="mt-5 whitespace-pre-line leading-8 text-slate-600">{{ $service->description }}</p>
                    @endif
                </div>

                @include('public.offers.partials.service-promotions')

                {{-- Enquiry --}}
                <div class="rounded-2xl border border-slate-200 bg-white p-5">
                    <a href="{{ route('public.services.enquiries.create', [$business, $service]) }}"
                        class="block rounded-xl bg-emerald-700 px-6 py-3 text-center font-semibold text-white transition hover:bg-emerald-800">
                        Send Enquiry
                    </a>

                    <p class="mt-3 text-xs leading-5 text-slate-500">
                        Enquiry bhejne se booking confirm nahi hoti. Business aapse baat karke details confirm karega.
                    </p>
                </div>

                {{-- Wishlist --}}
                @include('public.partials.wishlist-save', ['type' => 'service', 'itemId' => $service->id])

                {{-- Contact --}}
                @if ($whatsapp || $phone)
                    <div class="grid gap-3 sm:grid-cols-2">
                        @if ($whatsapp)
                            <a href="https://wa.me/{{ $whatsapp }}?text={{ rawurlencode($sellerMessage) }}"
                                target="_blank" rel="noopener noreferrer"
                                class="rounded-2xl bg-emerald-600 px-5 py-4 text-center font-black text-white">
                                WhatsApp Business
                            </a>
                        @endif

                        @if ($phone)
                            <a href="tel:{{ $phone }}"
                                class="rounded-2xl bg-slate-950 px-5 py-4 text-center font-black text-white">
                                Call Business
                            </a>
                        @endif
                    </div>
                @endif


{{-- Chat --}}
@auth
    @if ((int) auth()->id() !== (int) $business->owner_id)
        <form method="POST" action="{{ route('growya.chat.start', $business) }}">
            @csrf
            <input type="hidden" name="context_type" value="service">
            <input type="hidden" name="context_id" value="{{ $service->id }}">

            <button type="submit"
                class="w-full rounded-xl border border-emerald-700 px-5 py-3 font-semibold text-emerald-700 transition hover:bg-emerald-50">
                💬 Chat With Seller
            </button>
        </form>
    @endif
@else
    <a href="{{ route('login') }}"
        class="block rounded-xl border border-slate-300 px-5 py-3 text-center font-semibold text-slate-700 transition hover:border-emerald-600 hover:text-emerald-700">
        Login to Chat With Seller
    </a>
@endauth


                <div class="border-t border-slate-200 pt-5">
                    <h2 class="font-black text-slate-900">Offered by {{ $business->name }}</h2>
                    <a class="mt-2 inline-block text-sm font-bold text-emerald-700"
                        href="{{ route('public.business.home', $business->slug) }}">
                        Visit Business Showroom →
                    </a>
                </div>
            </section>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const main = document.querySelector('[data-main-service-image]');
            const thumbs = document.querySelectorAll('[data-photo-url]');

            thumbs.forEach(function(button) {
                button.addEventListener('click', function() {
                    if (!main) return;

                    main.src = button.dataset.photoUrl;
                    main.alt = button.dataset.photoAlt || 'Service image';

                    thumbs.forEach(function(b) {
                        b.classList.remove('border-emerald-600');
                        b.classList.add('border-slate-200');
                    });

                    button.classList.add('border-emerald-600');
                    button.classList.remove('border-slate-200');
                });
            });
        });
    </script>
@endsection
