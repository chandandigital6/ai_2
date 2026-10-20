<x-layouts::app :title="ucfirst($kind) . ' · ' . $business->name">
    <div class="mx-auto max-w-6xl space-y-6 p-4 md:p-6">
        <div>
            <p class="text-sm text-emerald-700">{{ $business->name }} / Catalog settings</p>
            <h1 class="text-2xl font-bold">{{ ucfirst($kind) }}</h1>
            <p class="mt-1 text-sm text-zinc-500">Your custom catalog belongs to this business. Platform defaults remain
                available in product and service forms.</p>
        </div>
        <nav class="flex gap-3">
            @foreach (['categories', 'attributes'] as $tab)
                <a href="{{ route('businesses.catalog.index', [$business, $tab]) }}"
                    class="rounded-xl border px-4 py-2 {{ $kind === $tab ? 'bg-emerald-700 text-white' : '' }}">{{ ucfirst($tab) }}</a>
            @endforeach
        </nav>
        @if (session('success'))
            <p role="status" class="rounded-xl bg-emerald-50 p-4 text-emerald-800">{{ session('success') }}</p>
        @endif
        @if ($errors->any())
            <div role="alert" class="rounded-xl bg-red-50 p-4 text-red-800">
                @foreach ($errors->all() as $error)
                    <p>{{ $error }}</p>
                @endforeach
            </div>
        @endif
        @php $canManage=app(\App\Services\BusinessAccessService::class)->can(auth()->user(),$business,$kind.'.manage'); @endphp
        @if ($canManage)
            <form method="POST" action="{{ route('businesses.catalog.store', [$business, $kind]) }}"
                class="space-y-4 rounded-2xl border bg-white p-5 dark:bg-zinc-900">@csrf
                <h2 class="font-semibold">Create {{ $kind === 'categories' ? 'category' : 'attribute' }}</h2>
                @include('businesses.catalog.fields', ['record' => null])
                <button class="rounded-xl bg-emerald-700 px-5 py-2.5 text-white">Create</button>
            </form>
        @endif
        @forelse($items as $item)
            <details class="rounded-2xl border bg-white p-5 dark:bg-zinc-900">
                <summary class="cursor-pointer font-semibold">{{ $item->name }} <span
                        class="ml-2 text-sm font-normal text-zinc-500">{{ $kind === 'categories' ? ucfirst($item->category_type) : $item->values->count() . ' values' }}</span>
                </summary>
                @if ($canManage)
                    <form method="POST" action="{{ route('businesses.catalog.update', [$business, $kind, $item->id]) }}"
                        class="mt-4 space-y-4">@csrf @method('PUT') @include('businesses.catalog.fields', ['record' => $item])<button
                            class="rounded-xl bg-emerald-700 px-5 py-2 text-white">Save</button></form>
                @endif
                @if ($kind === 'attributes')
                    <div class="my-4 flex flex-wrap gap-2">
                        @foreach ($item->values as $value)
                            <div class="flex items-center gap-2 rounded-lg border px-3 py-2 text-sm">
                                {{ $value->value }} @if ($canManage)
                                    <form method="POST"
                                        action="{{ route('businesses.catalog.values.destroy', [$business, $item->id, $value->id]) }}"
                                        onsubmit="return confirm('Delete this value?')">@csrf @method('DELETE')<button
                                            class="text-red-600" aria-label="Delete {{ $value->value }}">×</button>
                                    </form>
                                @endif
                            </div>
                        @endforeach
                    </div>
                    @if ($canManage)
                        <form method="POST"
                            action="{{ route('businesses.catalog.values.store', [$business, $item->id]) }}"
                            class="my-4 flex gap-2">@csrf<input name="value" required maxlength="255"
                                placeholder="New value, e.g. 22KT or XL"
                                class="w-full rounded-xl border bg-transparent px-3 py-2"><button
                                class="whitespace-nowrap rounded-xl border px-4 py-2">Add value</button></form>
                    @endif
                @endif
                @if ($canManage)
                    <form method="POST" action="{{ route('businesses.catalog.destroy', [$business, $kind, $item->id]) }}"
                        class="mt-4"
                        onsubmit="return confirm('Delete this catalog entry? Entries in use cannot be deleted.')">@csrf
                        @method('DELETE')<button class="text-sm text-red-600">Delete
                            {{ $kind === 'categories' ? 'category' : 'attribute' }}</button></form>
                @endif
            </details>
        @empty <div class="rounded-2xl border border-dashed p-10 text-center text-zinc-500">No custom
                {{ $kind }} yet.</div>
        @endforelse
        {{ $items->links() }}
    </div>
</x-layouts::app>
