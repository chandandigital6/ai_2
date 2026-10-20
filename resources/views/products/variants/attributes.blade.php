
<x-layouts::app :title="'Variant Attributes - '.$variant->name">

@php
    $canEdit = app(
        \App\Services\BusinessAccessService::class
    )->can(
        auth()->user(),
        $business,
        'products.update'
    );
@endphp

<div class="mx-auto max-w-5xl space-y-6 p-4 md:p-6">

    {{-- Header --}}
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold">
                Variant Attribute Mapping
            </h1>

            <p class="mt-1 text-sm text-zinc-500">
                {{ $business->name }} /
                {{ $product->name }} /
                {{ $variant->name }}
            </p>
        </div>

        <a
            href="{{ route('businesses.products.variants.index', [$business, $product]) }}"
            class="rounded-xl border border-zinc-300 px-4 py-2 text-sm dark:border-zinc-700"
        >
            Back to Variants
        </a>
    </div>

    @if(session('success'))
        <div class="rounded-xl bg-green-50 p-4 text-sm text-green-700 dark:bg-green-950 dark:text-green-200">
            {{ session('success') }}
        </div>
    @endif

    @if($errors->any())
        <div class="rounded-xl bg-red-50 p-4 text-sm text-red-700 dark:bg-red-950 dark:text-red-200">
            <ul class="list-disc pl-5">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- Variant Info --}}
    <div class="rounded-2xl border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-900">

        <div class="flex items-center gap-4">

            @if($variant->image)
                <img
                    src="{{ asset('storage/' . $variant->image) }}"
                    alt="{{ $variant->name }}"
                    class="h-24 w-24 rounded-xl object-contain"
                >
            @endif

            <div>
                <h2 class="text-lg font-semibold">
                    {{ $variant->name }}
                </h2>

                <p class="mt-1 text-sm text-zinc-500">
                    SKU: {{ $variant->sku ?: 'N/A' }}
                </p>

                <p class="mt-2 font-semibold">
                    ₹{{ number_format((float)($variant->offer_price ?? $variant->price), 2) }}
                </p>

                <p class="text-sm text-zinc-500">
                    Stock: {{ $variant->stock_quantity }}
                </p>
            </div>

        </div>
    </div>

    {{-- Attribute Mapping --}}
    <form
        method="POST"
        action="{{ route('businesses.products.variants.attributes.update', [$business, $product, $variant]) }}"
        class="space-y-5"
    >
        @csrf
        @method('PUT')

        <div class="rounded-2xl border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-900">

            <h2 class="text-lg font-semibold">
                Select Variant Attribute Values
            </h2>

            <p class="mt-1 text-sm text-zinc-500">
                Choose one value for each attribute.
                Leave a field empty if not applicable.
            </p>

            @if($attributes->isNotEmpty())

                <div class="mt-5 grid gap-5 md:grid-cols-2">

                    @foreach($attributes as $attribute)

                        <label class="block space-y-2">

                            <span class="text-sm font-semibold">
                                {{ $attribute->name }}
                            </span>

                            <select
                                name="attributes[{{ $attribute->id }}]"
                                @disabled(!$canEdit)
                                class="w-full rounded-xl border border-zinc-300 bg-white px-4 py-2.5 text-zinc-900 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white"
                            >
                                <option value="">
                                    Not Applicable
                                </option>

                                @foreach($attribute->values as $value)

                                    <option
                                        value="{{ $value->id }}"
                                        @selected(
                                            (string)old(
                                                "attributes.{$attribute->id}",
                                                $selected[$attribute->id] ?? ''
                                            ) === (string)$value->id
                                        )
                                    >
                                        {{ $value->value }}
                                    </option>

                                @endforeach

                            </select>

                        </label>

                    @endforeach

                </div>

                @if($canEdit)
                    <button
                        type="submit"
                        class="mt-6 rounded-xl bg-zinc-950 px-6 py-2.5 font-semibold text-white dark:bg-white dark:text-zinc-950"
                    >
                        Save Variant Attributes
                    </button>
                @endif

            @else

                <div class="mt-5 rounded-xl border border-dashed border-zinc-300 p-6 text-center text-sm text-zinc-500 dark:border-zinc-700">
                    No product attributes assigned yet.
                    Go to Product Attributes and select available values first.
                </div>

            @endif

        </div>

    </form>

    {{-- Saved Mapping --}}
    <div class="rounded-2xl border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-900">

        <h2 class="mb-4 text-lg font-semibold">
            Currently Saved Combination
        </h2>

        <div class="flex flex-wrap gap-3">

            @forelse($attributes as $attribute)

                @php
                    $selectedId = $selected[$attribute->id] ?? null;
                    $selectedValue = $attribute->values
                        ->firstWhere('id', $selectedId);
                @endphp

                @if($selectedValue)
                    <div class="rounded-xl bg-zinc-100 px-4 py-3 dark:bg-zinc-800">
                        <p class="text-xs text-zinc-500">
                            {{ $attribute->name }}
                        </p>
                        <p class="font-semibold">
                            {{ $selectedValue->value }}
                        </p>
                    </div>
                @endif

            @empty
                <p class="text-sm text-zinc-500">
                    No attributes available.
                </p>
            @endforelse

        </div>

    </div>

</div>

</x-layouts::app>
