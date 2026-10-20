
@extends('public.businesses.layout')

@section('title', 'Products | ' . $business->name)

@section('content')

<div class="mx-auto max-w-7xl space-y-8 px-4 py-10">

    <div>
        <h1 class="text-3xl font-bold">Our Products</h1>

        <p class="mt-2 text-zinc-500">
            Explore products offered by {{ $business->name }}
        </p>
    </div>

    @if($products->isNotEmpty())

        <div class="grid grid-cols-2 gap-4 md:grid-cols-3 lg:grid-cols-4">

            @foreach($products as $product)
                @include('public.businesses.partials.product-card')
            @endforeach

        </div>

        {{ $products->links() }}

    @else

        <div class="rounded-xl border border-zinc-200 bg-white p-10 text-center text-zinc-500">
            No products are available currently.
        </div>

    @endif

</div>

@endsection
