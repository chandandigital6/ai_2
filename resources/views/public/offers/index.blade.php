<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Current Offers & Promotions | Growya</title>
    <meta name="description" content="Explore current promotions from active, approved businesses on Growya.">
    
@include('public.partials.assets')
</head>

<body class="g-public">
    @include('public.partials.site-header')
    <main id="main-content" class="mx-auto max-w-7xl space-y-8 px-4 py-10 lg:px-8">
        <section class="rounded-3xl bg-gradient-to-r from-emerald-700 to-teal-700 px-6 py-12 text-white">
            <p class="text-xs font-bold uppercase tracking-widest text-emerald-100">Growya Marketplace</p>
            <h1 class="mt-2 text-3xl font-black sm:text-4xl">Offers & Promotions</h1>
            <p class="mt-3 text-emerald-50">Current offers from active, approved businesses.</p>
        </section>
        <form method="GET" action="{{ route('public.offers.index') }}"
            class="flex flex-wrap items-end gap-3 rounded-2xl bg-white p-5 shadow-sm">
            <label class="min-w-52 flex-1 text-sm font-semibold">Search offers
                <input type="search" name="q" value="{{ request('q') }}" maxlength="120"
                    placeholder="Offer or business name"
                    class="mt-2 w-full rounded-xl border border-slate-300 px-4 py-3">
            </label>
            <label class="text-sm font-semibold">Type
                <select name="type" class="mt-2 block w-full rounded-xl border border-slate-300 px-4 py-3">
                    <option value="">All types</option>
                    @foreach (['percentage' => 'Percentage', 'fixed' => 'Fixed Amount', 'announcement' => 'Promotion'] as $key => $label)
                        <option value="{{ $key }}" @selected(request('type') === $key)>{{ $label }}</option>
                    @endforeach
                </select>
            </label>
            <button type="submit" class="rounded-xl bg-emerald-700 px-6 py-3 font-bold text-white">Search</button>
            <a href="{{ route('public.offers.index') }}"
                class="rounded-xl border border-slate-300 px-6 py-3 font-semibold">Reset</a>
        </form>
        @if ($offers->isEmpty())
            <div class="rounded-2xl bg-white p-10 text-center text-slate-600">No active offers match your search.</div>
        @else
            <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($offers as $offer)
                    @include('public.offers.partials.offer-card')
                @endforeach
            </div>
            {{ $offers->links() }}
        @endif
    </main>
    @include('public.partials.site-footer')
</body>

</html>
