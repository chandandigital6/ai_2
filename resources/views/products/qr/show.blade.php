
<x-layouts::app :title="'Product QR - '.$product->name">

@php
    $canEdit = app(
        \App\Services\BusinessAccessService::class
    )->can(auth()->user(), $business, 'products.update');

    $qrExists = $product->qr_code
        && \Illuminate\Support\Facades\Storage::disk('public')
            ->exists($product->qr_code);
@endphp

<div class="mx-auto max-w-4xl space-y-6 p-4 md:p-6">

    <div class="flex items-center justify-between gap-3">
        <div>
            <h1 class="text-2xl font-bold">
                Product QR Code
            </h1>

            <p class="text-sm text-zinc-500">
                {{ $business->name }} / {{ $product->name }}
            </p>
        </div>

        <a
            href="{{ route('businesses.products.show', [$business, $product]) }}"
            class="rounded-xl border border-zinc-300 px-4 py-2 dark:border-zinc-700"
        >
            Back
        </a>
    </div>

    @if(session('success'))
        <div class="rounded-xl bg-green-50 p-4 text-green-700">
            {{ session('success') }}
        </div>
    @endif

    <div class="rounded-2xl border border-zinc-200 bg-white p-6 text-center dark:border-zinc-700 dark:bg-zinc-900">

        <h2 class="text-xl font-bold">
            {{ $product->name }}
        </h2>

        <p class="mt-2 text-sm text-zinc-500">
            SKU: {{ $product->sku ?: 'N/A' }}
        </p>

        <p class="mt-2 text-2xl font-bold">
            ₹{{ number_format((float)($product->offer_price ?? $product->price), 2) }}
        </p>

        @if($qrExists)

            <img
                src="{{ asset('storage/' . $product->qr_code) }}?v={{ $product->updated_at?->timestamp }}"
                alt="QR Code for {{ $product->name }}"
                class="mx-auto mt-6 h-64 w-64 rounded-xl border border-zinc-200 bg-white p-3"
            >

            <p class="mt-3 text-sm text-green-600">
                QR Code Generated
            </p>

        @else

            <div class="mx-auto mt-6 flex h-64 w-64 items-center justify-center rounded-xl border border-dashed border-zinc-300 text-zinc-500">
                QR Code Not Generated
            </div>

        @endif

        <p class="mt-5 break-all text-sm text-zinc-500">
            {{ $publicUrl }}
        </p>

        <div class="mt-6 flex flex-wrap justify-center gap-3">

            @if($canEdit)
                <form method="POST"
                      action="{{ route('businesses.products.qr.generate', [$business, $product]) }}">
                    @csrf

                    <button
                        class="rounded-xl bg-zinc-950 px-5 py-2.5 text-sm font-semibold text-white dark:bg-white dark:text-black"
                    >
                        {{ $qrExists ? 'Regenerate QR' : 'Generate QR' }}
                    </button>
                </form>
            @endif

            @if($qrExists)
                <a
                    href="{{ route('businesses.products.qr.download', [$business, $product]) }}"
                    class="rounded-xl border border-zinc-300 px-5 py-2.5 text-sm font-semibold dark:border-zinc-700"
                >
                    Download PNG
                </a>

                <a
                    href="{{ route('businesses.products.qr.print', [$business, $product]) }}"
                    target="_blank"
                    rel="noopener"
                    class="rounded-xl border border-zinc-300 px-5 py-2.5 text-sm font-semibold dark:border-zinc-700"
                >
                    Print Label
                </a>
            @endif

            <a
                href="{{ $publicUrl }}"
                target="_blank"
                rel="noopener noreferrer"
                class="rounded-xl border border-zinc-300 px-5 py-2.5 text-sm font-semibold dark:border-zinc-700"
            >
                Open Public Product
            </a>

        </div>
    </div>
</div>

</x-layouts::app>
