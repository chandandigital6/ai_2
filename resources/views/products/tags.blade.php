
<x-layouts::app :title="'Product Tags - '.$product->name">

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
                Manage Product Tags
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
        action="{{ route('businesses.products.tags.update', [$business, $product]) }}"
        class="rounded-2xl border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-900"
    >
        @csrf
        @method('PUT')

        <div class="mb-5">
            <h2 class="text-lg font-semibold">
                Select Product Tags
            </h2>

            <p class="mt-1 text-sm text-zinc-500">
                Select multiple tags for this product.
            </p>
        </div>

        @if($tags->isNotEmpty())

            <div class="mb-5">
                <input
                    type="search"
                    id="tag-search"
                    placeholder="Search tags..."
                    class="w-full rounded-xl border border-zinc-300 bg-white px-3 py-2.5 dark:border-zinc-700 dark:bg-zinc-800"
                >
            </div>

            <div
                id="product-tag-list"
                class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3"
            >
                @foreach($tags as $tag)
                    <label
                        data-tag-label="{{ strtolower($tag->name) }}"
                        class="flex cursor-pointer items-center gap-3 rounded-xl border border-zinc-200 p-3 dark:border-zinc-700"
                    >
                        <input
                            type="checkbox"
                            name="tags[]"
                            value="{{ $tag->id }}"
                            @checked(in_array(
                                (int) $tag->id,
                                old('tags', $selectedTags)
                            ))
                            @disabled(!$canEdit)
                            class="h-4 w-4 rounded border-zinc-300"
                        >

                        <span class="text-sm font-medium">
                            {{ $tag->name }}
                        </span>
                    </label>
                @endforeach
            </div>

            @if($canEdit)
                <button
                    type="submit"
                    class="mt-6 rounded-xl bg-zinc-950 px-6 py-2.5 font-semibold text-white dark:bg-white dark:text-zinc-950"
                >
                    Save Product Tags
                </button>
            @endif

        @else
            <p class="text-sm text-zinc-500">
                No tags created yet.
            </p>
        @endif

    </form>

    <div class="rounded-2xl border border-zinc-200 p-5 dark:border-zinc-700">
        <h3 class="mb-3 font-semibold">Selected Tags</h3>

        <div class="flex flex-wrap gap-2">
            @forelse($tags->whereIn('id', $selectedTags) as $tag)
                <span class="rounded-full bg-zinc-100 px-3 py-1.5 text-sm dark:bg-zinc-800">
                    {{ $tag->name }}
                </span>
            @empty
                <span class="text-sm text-zinc-500">
                    No tags assigned.
                </span>
            @endforelse
        </div>
    </div>

</div>

<script>
(function () {
    const search = document.getElementById('tag-search');

    if (!search || search.dataset.initialized === '1') {
        return;
    }

    search.dataset.initialized = '1';

    search.addEventListener('input', function () {
        const keyword = this.value.toLowerCase().trim();

        document.querySelectorAll('[data-tag-label]')
            .forEach(function (item) {
                const name = item.dataset.tagLabel || '';
                item.hidden = !name.includes(keyword);
            });
    });
})();
</script>

</x-layouts::app>
