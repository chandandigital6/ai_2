
@extends('public.businesses.layout')

@section('title', 'Contact | ' . $business->name)

@section('content')

@php
    $phone = preg_replace('/[^0-9+]/', '', $business->phone ?: '');
    $wa = preg_replace('/\D/', '', $business->whatsapp ?: '');

    if (strlen($wa) === 10) {
        $wa = '91' . $wa;
    }

    $addressText = implode(', ', array_filter([
        $business->address,
        $business->pincode,
    ]));
@endphp

<div class="mx-auto max-w-5xl space-y-8 px-4 py-12">

    <div>
        <h1 class="text-3xl font-bold">
            Contact Us
        </h1>

        <p class="mt-2 text-zinc-500">
            Get in touch with {{ $business->name }}
        </p>
    </div>

    <div class="grid gap-6 md:grid-cols-2">

        <div class="space-y-5 rounded-2xl border border-zinc-200 bg-white p-6">

            <h2 class="text-xl font-bold">
                Business Details
            </h2>

            @if($business->phone)
                <div>
                    <p class="text-sm text-zinc-500">Phone</p>

                    <a href="tel:{{ $phone }}" class="font-semibold text-blue-600">
                        {{ $business->phone }}
                    </a>
                </div>
            @endif

            @if($business->email)
                <div>
                    <p class="text-sm text-zinc-500">Email</p>

                    <a href="mailto:{{ $business->email }}" class="font-semibold text-blue-600">
                        {{ $business->email }}
                    </a>
                </div>
            @endif

            @if($addressText)
                <div>
                    <p class="text-sm text-zinc-500">Address</p>
                    <p class="font-medium">{{ $addressText }}</p>
                </div>
            @endif

            <div class="flex flex-wrap gap-3">

                @if($phone)
                    <a
                        href="tel:{{ $phone }}"
                        class="rounded-xl bg-zinc-950 px-5 py-3 font-semibold text-white"
                    >
                        Call Business
                    </a>
                @endif

                @if($wa)
                    <a
                        href="https://wa.me/{{ $wa }}"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="rounded-xl bg-green-600 px-5 py-3 font-semibold text-white"
                    >
                        WhatsApp
                    </a>
                @endif

            </div>
        </div>

        <div class="rounded-2xl border border-zinc-200 bg-white p-6">

            <h2 class="text-xl font-bold">
                Visit Us
            </h2>

            <p class="mt-3 text-zinc-600">
                {{ $addressText ?: 'Contact the business for its location.' }}
            </p>

            @if($addressText)
                <a
                    href="https://www.google.com/maps/search/?api=1&query={{ urlencode($addressText) }}"
                    target="_blank"
                    rel="noopener noreferrer"
                    class="mt-6 inline-block rounded-xl border border-zinc-300 px-5 py-3 font-semibold"
                >
                    View on Google Maps
                </a>
            @endif

        </div>

    </div>

</div>

@endsection
