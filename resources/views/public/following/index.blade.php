<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>My Following | Growya</title>

    
@include('public.partials.assets')
</head>
<body class="g-public">
    @include('public.partials.site-header')

    <main id="main-content" class="mx-auto max-w-6xl px-4 py-10">
        <h1 class="text-3xl font-bold">My Following</h1>
        <p class="mt-2 text-slate-600">Businesses you follow on Growya.</p>

        @if(session('success'))
            <p class="mt-5 rounded-xl bg-emerald-50 p-4 text-emerald-800">{{ session('success') }}</p>
        @endif

        <div class="mt-8 grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
            @forelse($following as $follow)
                @php $shop = $follow->business; @endphp
                @if($shop)
                <article class="flex flex-col gap-4 rounded-2xl border bg-white p-5 shadow-sm">
                    <div class="min-h-0 flex-1">
                        <h2 class="text-lg font-bold">{{ $shop->name }}</h2>
                        <p class="mt-2 line-clamp-3 text-sm text-slate-600">{{ $shop->description }}</p>
                        <p class="mt-3 text-xs text-slate-500">{{ number_format($shop->followers_count) }} followers</p>
                    </div>
                    <div class="flex flex-wrap gap-3">
                        <a href="{{ route('public.business.home', $shop->slug) }}" class="rounded-xl bg-emerald-700 px-4 py-2 text-sm font-semibold text-white">View Store</a>
                        <form method="POST" action="{{ route('public.business.unfollow', $shop) }}">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="rounded-xl border px-4 py-2 text-sm font-semibold">Unfollow</button>
                        </form>
                    </div>
                </article>
                @endif
            @empty
                <div class="rounded-xl border bg-white p-8 text-slate-600 sm:col-span-2 lg:col-span-3">
                    You are not following any public businesses yet.
                    <a class="font-semibold text-emerald-700 underline" href="{{ route('public.marketplace.index') }}">Explore Marketplace</a>
                </div>
            @endforelse
        </div>
        <div class="mt-8">{{ $following->links() }}</div>
    </main>
@include('public.partials.site-footer')
</body>
</html>
