
<x-layouts::app :title="'Product Attributes - '.$product->name">

@php
    $canEdit = app(
        \App\Services\BusinessAccessService::class
    )->can(
        auth()->user(),
        $business,
        'products.update'
    );
@endphp

<div class="mx-auto max-w-6xl space-y-6 p-4 md:p-6">

    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold">
                Product Attributes
            </h1>

            <p class="mt-1 text-sm text-zinc-500">
                {{ $business->name }} / {{ $product->name }}
            </p>
        </div>

        <a
            href="{{ route('businesses.products.show', [$business, $product]) }}"
            class="rounded-xl border border-zinc-300 px-4 py-2 text-sm dark:border-zinc-700"
        >
            Back to Product
        </a>
    </div>

    @if(session('success'))
        <div class="rounded-xl bg-green-50 p-4 text-sm text-green-700 dark:bg-green-950 dark:text-green-200">
            {{ session('success') }}
        </div>
    @endif

    @if($errors->any())
        <div class="rounded-xl bg-red-50 p-4 text-sm text-red-700 dark:bg-red-950 dark:text-red-200">
            {{ $errors->first() }}
        </div>
    @endif

    <form
        method="POST"
        action="{{ route('businesses.products.attributes.update', [$business, $product]) }}"
        class="space-y-6"
    >
        @csrf
        @method('PUT')

        @forelse($attributes as $attribute)

            <div class="rounded-2xl border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-900">

                <div class="mb-4 flex items-center justify-between gap-3">
                    <h2 class="text-lg font-semibold">
                        {{ $attribute->name }}
                    </h2>

                    <span class="text-xs text-zinc-500">
                        {{ $attribute->values->count() }} Values
                    </span>
                </div>

                <div class="grid gap-3 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4">

                    @foreach($attribute->values as $value)

                        <label class="flex cursor-pointer items-center gap-3 rounded-xl border border-zinc-200 p-3 dark:border-zinc-700">

                            <input
                                type="checkbox"
                                name="values[]"
                                value="{{ $value->id }}"
                                @checked(in_array(
                                    (int) $value->id,
                                    old('values', $selectedValues)
                                ))
                                @disabled(!$canEdit)
                                class="h-4 w-4 rounded border-zinc-300"
                            >

                            <span class="text-sm font-medium">
                                {{ $value->value }}
                            </span>

                        </label>

                    @endforeach

                </div>

            </div>

        @empty

            <div class="rounded-2xl border border-dashed border-zinc-300 p-10 text-center text-zinc-500 dark:border-zinc-700">
                No attributes available. Create attributes and values first.
            </div>

        @endforelse

        @if($canEdit && $attributes->isNotEmpty())
            <button
                type="submit"
                class="rounded-xl bg-zinc-950 px-6 py-2.5 font-semibold text-white dark:bg-white dark:text-zinc-950"
            >
                Save Product Attributes
            </button>
        @endif

    </form>

    {{-- Currently Assigned Values --}}
    <div class="rounded-2xl border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-900">

        <h2 class="mb-4 text-lg font-semibold">
            Assigned Attribute Values
        </h2>

        @php
            $hasAssigned = false;
        @endphp

        <div class="space-y-4">

            @foreach($attributes as $attribute)

                @php
                    $assignedValues = $attribute->values->filter(
                        fn ($value) => in_array(
                            (int) $value->id,
                            $selectedValues
                        )
                    );
                @endphp

                @if($assignedValues->isNotEmpty())

                    @php $hasAssigned = true; @endphp

                    <div>
                        <h3 class="mb-2 text-sm font-semibold">
                            {{ $attribute->name }}
                        </h3>

                        <div class="flex flex-wrap gap-2">

                            @foreach($assignedValues as $value)
                                <span class="rounded-full bg-zinc-100 px-3 py-1.5 text-sm dark:bg-zinc-800">
                                    {{ $value->value }}
                                </span>
                            @endforeach

                        </div>
                    </div>

                @endif

            @endforeach

            @if(!$hasAssigned)
                <p class="text-sm text-zinc-500">
                    No attribute values assigned yet.
                </p>
            @endif

        </div>
    </div>

</div>

</x-layouts::app>
