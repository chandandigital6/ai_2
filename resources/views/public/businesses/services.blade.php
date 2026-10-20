
@extends('public.businesses.layout')

@section('title', 'Services | ' . $business->name)

@section('content')

<div class="mx-auto max-w-7xl space-y-8 px-4 py-10">

    <div>
        <h1 class="text-3xl font-bold">
            Our Services
        </h1>

        <p class="mt-2 text-zinc-500">
            Explore services offered by {{ $business->name }}
        </p>
    </div>

    @if($services->isNotEmpty())

        <div class="grid gap-5 md:grid-cols-2 lg:grid-cols-3">

            @foreach($services as $service)
                @include('public.businesses.partials.service-card')
            @endforeach

        </div>

        {{ $services->links() }}

    @else

        <div class="rounded-xl border border-zinc-200 bg-white p-10 text-center text-zinc-500">
            No services available currently.
        </div>

    @endif

</div>

@endsection
