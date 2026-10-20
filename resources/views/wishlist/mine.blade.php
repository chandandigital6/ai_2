@php
    $productItems = $products->filter(fn($f) => $f->product && $f->product->business);
    $serviceItems = $services->map(fn($f) => $serviceModels->get($f->service_id))->filter(fn($s) => $s && $s->business);

    $statusLabels = [
        'new' => 'Saved',
        'contacted' => 'Seller contacted',
        'follow_up' => 'Follow-up pending',
        'converted' => 'Converted',
        'closed' => 'Closed',
    ];
@endphp

<x-layouts::app title="My Wishlist">
    <div id="main-content" class="mx-auto max-w-6xl space-y-8 p-6">
        <div>
            <h1 class="text-3xl font-bold">My Wishlist &amp; Favorites</h1>
            <p class="text-sm text-zinc-500">Products and services you've saved.</p>
        </div>

        @if (session('success'))
            <div class="rounded-xl bg-emerald-50 p-4 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-200">
                {{ session('success') }}
            </div>
        @endif

        {{-- Products --}}
        <section class="space-y-4">
            <h2 class="text-xl font-semibold">Products <span
                    class="text-sm font-normal text-zinc-500">({{ $productItems->count() }})</span></h2>

            <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                @forelse ($productItems as $favorite)
                    @php
                        $product = $favorite->product;
                        $interest = $interests->get('product:' . $favorite->product_id);
                    @endphp
                    <article
                        class="space-y-3 rounded-2xl border bg-white p-5 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
                        <div>
                            <h3 class="font-semibold">{{ $product->name }}</h3>
                            <p class="text-sm text-zinc-500">{{ $product->business->name }}</p>
                        </div>

                        @if ($interest)
                            <div class="flex flex-wrap gap-2 text-xs">
                                <span class="rounded-full bg-zinc-100 px-2 py-1 dark:bg-zinc-800">
                                    {{ $statusLabels[$interest->status] ?? ucfirst(str_replace('_', ' ', $interest->status)) }}
                                </span>
                                <span
                                    class="rounded-full px-2 py-1 {{ $interest->contact_consent ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300' : 'bg-amber-50 text-amber-700 dark:bg-amber-950 dark:text-amber-300' }}">
                                    {{ $interest->contact_consent ? 'Seller can contact you' : 'Contact not allowed' }}
                                </span>
                            </div>
                        @endif

                        <div class="flex flex-wrap items-center gap-3">
                            <a class="text-emerald-700 underline dark:text-emerald-400"
                                href="{{ route('public.business.product', [$product->business->slug, $product->slug]) }}">View
                                product</a>

                            <form method="POST"
                                action="{{ route('growya.wishlist.remove', ['product', $favorite->product_id]) }}"
                                onsubmit="return confirm('Remove this product from your wishlist?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="text-red-600 underline">Remove</button>
                            </form>
                        </div>
                    </article>
                @empty
                    <p class="text-zinc-500 md:col-span-2 lg:col-span-3">No products saved.</p>
                @endforelse
            </div>
        </section>

        {{-- Services --}}
        <section class="space-y-4">
            <h2 class="text-xl font-semibold">Services <span
                    class="text-sm font-normal text-zinc-500">({{ $serviceItems->count() }})</span></h2>

            <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                @forelse ($serviceItems as $service)
                    @php $interest = $interests->get('service:' . $service->id); @endphp
                    <article
                        class="space-y-3 rounded-2xl border bg-white p-5 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
                        <div>
                            <h3 class="font-semibold">{{ $service->name }}</h3>
                            <p class="text-sm text-zinc-500">{{ $service->business->name }}</p>
                        </div>

                        @if ($interest)
                            <div class="flex flex-wrap gap-2 text-xs">
                                <span class="rounded-full bg-zinc-100 px-2 py-1 dark:bg-zinc-800">
                                    {{ $statusLabels[$interest->status] ?? ucfirst(str_replace('_', ' ', $interest->status)) }}
                                </span>
                                <span
                                    class="rounded-full px-2 py-1 {{ $interest->contact_consent ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300' : 'bg-amber-50 text-amber-700 dark:bg-amber-950 dark:text-amber-300' }}">
                                    {{ $interest->contact_consent ? 'Seller can contact you' : 'Contact not allowed' }}
                                </span>
                            </div>
                        @endif

                        <div class="flex flex-wrap items-center gap-3">
                            <a class="text-emerald-700 underline dark:text-emerald-400"
                                href="{{ route('public.business.service', [$service->business->slug, $service->slug]) }}">View
                                service</a>

                            <form method="POST"
                                action="{{ route('growya.wishlist.remove', ['service', $service->id]) }}"
                                onsubmit="return confirm('Remove this service from your wishlist?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="text-red-600 underline">Remove</button>
                            </form>
                        </div>
                    </article>
                @empty
                    <p class="text-zinc-500 md:col-span-2 lg:col-span-3">No services saved.</p>
                @endforelse
            </div>
        </section>
    </div>
</x-layouts::app>
