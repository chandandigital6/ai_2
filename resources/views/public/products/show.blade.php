
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>{{ $product->meta_title ?: $product->name }}</title>

    <meta name="description"
          content="{{ $product->meta_description ?: \Illuminate\Support\Str::limit(strip_tags($product->description ?? ''), 155) }}">

    <meta name="robots" content="index,follow">

    
@include('public.partials.assets')
</head>

<body class="g-public">
@include('public.partials.site-header')


    <header class="border-b border-zinc-200 bg-white">
        <div class="mx-auto max-w-5xl px-4 py-5">
            <h1 class="text-xl font-bold">
                {{ $business->name }}
            </h1>
            <p class="text-sm text-zinc-500">
                Product Details
            </p>
        </div>
    </header>

    <main id="main-content" class="mx-auto max-w-5xl space-y-6 p-4 py-8">

        <div class="grid gap-8 rounded-2xl bg-white p-5 shadow-sm md:grid-cols-2">

            <div>
                @if($product->featured_image)
                    <img
                        src="{{ asset('storage/' . $product->featured_image) }}"
                        alt="{{ $product->name }}"
                        class="h-80 w-full rounded-xl object-contain"
                    >
                @else
                    <div class="flex h-80 items-center justify-center rounded-xl bg-zinc-100 text-zinc-500">
                        No Product Image
                    </div>
                @endif

                @if($product->images->isNotEmpty())
                    <div class="mt-4 grid grid-cols-4 gap-2">
                        @foreach($product->images as $image)
                            <img
                                src="{{ asset('storage/' . $image->image) }}"
                                alt="{{ $image->alt_text ?: $product->name }}"
                                class="h-20 w-full rounded-lg border object-contain"
                                loading="lazy"
                            >
                        @endforeach
                    </div>
                @endif
            </div>

            <div class="space-y-4">

                <div>
                    <p class="text-sm text-zinc-500">
                        {{ $product->category?->name ?? 'Product' }}
                    </p>

                    <h2 class="mt-1 text-3xl font-bold">
                        {{ $product->name }}
                    </h2>
                </div>

                <div>
                    <span class="text-3xl font-bold">
                        ₹{{ number_format((float)($product->offer_price ?? $product->price), 2) }}
                    </span>

                    @if($product->offer_price !== null)
                        <span class="ml-2 text-zinc-500 line-through">
                            ₹{{ number_format((float)$product->price, 2) }}
                        </span>
                    @endif
                </div>

                <div class="text-sm">
                    <span class="font-semibold">SKU:</span>
                    {{ $product->sku ?: 'N/A' }}
                </div>

                <div class="text-sm">
                    <span class="font-semibold">Availability:</span>
                    {{ $product->stock_status === 'in_stock'
                        ? 'In Stock'
                        : 'Out of Stock' }}
                </div>

                <div class="whitespace-pre-line text-sm leading-7 text-zinc-600">
                    {{ $product->description }}
                </div>

                @if($product->variants->isNotEmpty())
                    <div class="border-t pt-4">
                        <h3 class="mb-3 font-semibold">
                            Available Variants
                        </h3>

                        <div class="space-y-2">
                            @foreach($product->variants as $variant)
                                @if($variant->status === 'active')
                                    <div class="rounded-lg border p-3 text-sm">
                                        <div class="font-semibold">
                                            {{ $variant->name }}
                                        </div>
                                        <div>
                                            ₹{{ number_format((float)($variant->offer_price ?? $variant->price), 2) }}
                                        </div>
                                        <div class="text-zinc-500">
                                            Stock: {{ $variant->stock_quantity }}
                                        </div>
                                    </div>
                                @endif
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>
        </div>

        @if(!empty($product->specifications))
            <section class="rounded-2xl bg-white p-5 shadow-sm">
                <h3 class="mb-4 text-xl font-semibold">
                    Specifications
                </h3>

                <table class="w-full text-left text-sm">
                    @foreach($product->specifications as $spec)
                        <tr class="border-b">
                            <th class="w-1/3 py-3">
                                {{ $spec['name'] ?? '' }}
                            </th>
                            <td class="py-3">
                                {{ $spec['value'] ?? '' }}
                            </td>
                        </tr>
                    @endforeach
                </table>
            </section>
        @endif



      

    </main>

@include('public.partials.site-footer')
</body>
</html>
