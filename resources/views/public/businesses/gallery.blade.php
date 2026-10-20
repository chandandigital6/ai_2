
@extends('public.businesses.layout')

@section('title', 'Gallery | ' . $business->name)

@section('content')

<div class="mx-auto max-w-7xl space-y-8 px-4 py-10">

    <div>
        <h1 class="text-3xl font-bold">
            Business Gallery
        </h1>

        <p class="mt-2 text-zinc-500">
            Photos, Videos, Team and Certificates
        </p>
    </div>

    @if($gallery->isNotEmpty())

        <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">

            @foreach($gallery as $media)

                <div class="overflow-hidden rounded-2xl border border-zinc-200 bg-white">

                    @if($media->is_video)
                        <video
                            controls
                            preload="metadata"
                            src="{{ $media->media_url }}"
                            class="aspect-video w-full bg-black object-contain"
                        ></video>
                    @else
                        <a
                            href="{{ $media->media_url }}"
                            target="_blank"
                            rel="noopener noreferrer"
                        >
                            <img
                                src="{{ $media->media_url }}"
                                alt="{{ $media->title ?: 'Business Photo' }}"
                                loading="lazy"
                                class="aspect-video w-full object-cover"
                            >
                        </a>
                    @endif

                    <div class="space-y-2 p-4">

                        <span class="text-xs font-semibold uppercase text-blue-600">
                            {{ $media->media_type }}
                        </span>

                        <h3 class="font-semibold">
                            {{ $media->title ?: 'Gallery Media' }}
                        </h3>

                        @if($media->description)
                            <p class="text-sm text-zinc-600">
                                {{ $media->description }}
                            </p>
                        @endif

                    </div>

                </div>

            @endforeach

        </div>

        {{ $gallery->links() }}

    @else

        <p class="rounded-xl bg-white p-10 text-center text-zinc-500">
            No gallery media available.
        </p>

    @endif

</div>

@endsection
