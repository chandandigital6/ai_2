
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, initial-scale=1">

    <meta name="robots" content="noindex,follow">

    <title>
        Enquire - {{ $service->name }} | {{ $business->name }}
    </title>

    

     
@include('public.partials.assets')
</head>

<body class="g-public">
@include('public.partials.site-header')


<div class="mx-auto max-w-3xl px-4 py-8">

    <div class="mb-6">
        <p class="text-sm text-zinc-500">
            {{ $business->name }}
        </p>

        <h1 class="mt-2 text-2xl font-bold">
            Service Enquiry
        </h1>

        <p class="mt-1 text-zinc-600">
            {{ $service->name }}
        </p>
    </div>

    @if(session('success'))
        <div class="mb-5 rounded-xl border border-green-200 bg-green-50 p-4 text-green-700">
            {{ session('success') }}
        </div>
    @endif

    @if($errors->any())
        <div class="mb-5 rounded-xl border border-red-200 bg-red-50 p-4 text-red-700">
            <ul class="list-disc pl-5">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="mb-5 rounded-2xl border border-zinc-200 bg-white p-5">

        <h2 class="text-lg font-semibold">
            {{ $service->name }}
        </h2>

        <p class="mt-2 text-sm text-zinc-600">
            @if($service->price_type === 'free')
                Free Service
            @elseif($service->price_type === 'on_request')
                Price On Request
            @else
                @if($service->price_type === 'starting_from')
                    Starting From
                @endif

                ₹{{ number_format(
                    (float)($service->offer_price ?? $service->price),
                    2
                ) }}
            @endif
        </p>

        @if($service->description)
            <p class="mt-3 whitespace-pre-line text-sm text-zinc-600">
                {{ $service->description }}
            </p>
        @endif
    </div>

    <form
        method="POST"
        action="{{ route('public.services.enquiries.store', [$business, $service]) }}"
        class="space-y-5 rounded-2xl border border-zinc-200 bg-white p-6 shadow-sm"
    >
        @csrf

        <h2 class="text-lg font-bold">
            Your Details
        </h2>

        <label class="block space-y-2">
            <span class="text-sm font-medium">
                Full Name *
            </span>

            <input
                type="text"
                name="customer_name"
                required
                maxlength="255"
                autocomplete="name"
                value="{{ old('customer_name') }}"
                placeholder="Enter your full name"
                class="w-full rounded-xl border border-zinc-300 px-4 py-3"
            >
        </label>

        <label class="block space-y-2">
            <span class="text-sm font-medium">
                Mobile Number *
            </span>

            <input
                type="tel"
                name="phone"
                required
                maxlength="10"
                pattern="[6-9][0-9]{9}"
                inputmode="numeric"
                autocomplete="tel"
                value="{{ old('phone') }}"
                placeholder="10-digit mobile number"
                class="w-full rounded-xl border border-zinc-300 px-4 py-3"
            >
        </label>

        <div class="grid gap-4 sm:grid-cols-2">

            <label class="block space-y-2">
                <span class="text-sm font-medium">
                    Preferred Date
                </span>

                <input
                    type="date"
                    name="preferred_date"
                    min="{{ now()->toDateString() }}"
                    value="{{ old('preferred_date') }}"
                    class="w-full rounded-xl border border-zinc-300 px-4 py-3"
                >
            </label>

            <label class="block space-y-2">
                <span class="text-sm font-medium">
                    Preferred Time
                </span>

                <input
                    type="time"
                    name="preferred_time"
                    value="{{ old('preferred_time') }}"
                    class="w-full rounded-xl border border-zinc-300 px-4 py-3"
                >
            </label>
        </div>

        <label class="block space-y-2">
            <span class="text-sm font-medium">
                Message
            </span>

            <textarea
                name="message"
                rows="5"
                maxlength="3000"
                placeholder="Tell us what you need..."
                class="w-full rounded-xl border border-zinc-300 px-4 py-3"
            >{{ old('message') }}</textarea>
        </label>

        {{-- Hidden honeypot --}}
        <div
            aria-hidden="true"
            style="position:absolute;left:-9999px;"
        >
            <label>
                Website
                <input
                    type="text"
                    name="website"
                    value=""
                    tabindex="-1"
                    autocomplete="off"
                >
            </label>
        </div>

        <p class="text-xs text-zinc-500">
            By submitting, you agree to share these details
            with the business so they can respond to your enquiry.
        </p>

        <button
            type="submit"
            class="w-full rounded-xl bg-zinc-950 px-6 py-3 font-semibold text-white"
        >
            Submit Enquiry
        </button>
    </form>
</div>

@include('public.partials.site-footer')
</body>
</html>
