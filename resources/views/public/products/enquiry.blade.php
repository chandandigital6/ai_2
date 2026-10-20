<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Enquire - {{ $product->name }}</title>
    
      

@include('public.partials.assets')
</head>

<body class="g-public">
@include('public.partials.site-header')

    <main id="main-content" class="mx-auto max-w-xl px-4 py-12"><a class="text-emerald-700 underline"
            href="{{ route('public.business.product', [$business->slug, $product->slug]) }}">← Back to product</a>
        <div class="my-6">
            <h1 class="text-2xl font-bold">Product Enquiry</h1>
            <p class="text-slate-600">{{ $product->name }} · {{ $business->name }}</p>
        </div>
        @if (session('success'))
            <div class="rounded bg-green-100 p-3 mb-4">{{ session('success') }}</div>
            @endif@if ($errors->any())
                <div class="rounded bg-red-100 p-3 mb-4">{{ $errors->first() }}</div>
            @endif
            <form method="POST" action="{{ route('public.products.enquiry.store', [$business, $product]) }}"
                class="bg-white rounded-2xl p-6 shadow space-y-4">@csrf<label class="block">Your name *<input
                        name="name" required maxlength="255" value="{{ old('name') }}"
                        class="block mt-1 w-full rounded-lg border p-3"></label><label class="block">Mobile number
                    *<input name="phone" required pattern="[6-9][0-9]{9}" maxlength="10" inputmode="numeric"
                        value="{{ old('phone') }}" class="block mt-1 w-full rounded-lg border p-3"></label><label
                    class="block">Quantity *<input type="number" name="quantity" min="1" max="100000"
                        required value="{{ old('quantity', 1) }}"
                        class="block mt-1 w-full rounded-lg border p-3"></label><label class="block">Message
                    <textarea name="message" maxlength="3000" rows="4" class="block mt-1 w-full rounded-lg border p-3">{{ old('message') }}</textarea>
                </label>
                <div style="position:absolute;left:-9999px" aria-hidden="true"><input name="website" tabindex="-1"
                        autocomplete="off"></div><button
                    class="w-full bg-emerald-700 text-white rounded-xl py-3 font-semibold">Send Enquiry</button>
                <p class="text-xs text-slate-500">Your contact details will be shared with this business to respond to
                    your enquiry.</p>
            </form>
    </main>
@include('public.partials.site-footer')
</body>

</html>
