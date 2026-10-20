
<x-layouts::app :title="'Inventory - '.$product->name">

@php
    $canEdit = app(
        \App\Services\BusinessAccessService::class
    )->can(
        auth()->user(),
        $business,
        'products.update'
    );
@endphp

<div class="mx-auto max-w-7xl space-y-6 p-4 md:p-6">

    {{-- Header --}}
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold">
                Product Inventory
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
            <ul class="list-disc pl-5">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- Main Product Stock --}}
    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">

        <div class="rounded-2xl border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-900">

            <p class="text-sm text-zinc-500">
                Main Product Stock
            </p>

            <p class="mt-2 text-3xl font-bold">
                {{ $product->stock_quantity }}
            </p>

            <span class="mt-2 inline-block text-xs text-zinc-500">
                {{ $product->stock_status }}
            </span>

            @if($product->stock_quantity <= $lowStockLimit)
                <p class="mt-3 text-sm font-semibold text-red-600">
                    Low Stock Alert
                </p>
            @endif

        </div>

        <div class="rounded-2xl border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-900">

            <p class="text-sm text-zinc-500">
                Total Variants
            </p>

            <p class="mt-2 text-3xl font-bold">
                {{ $variants->count() }}
            </p>

        </div>

        <div class="rounded-2xl border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-900">

            <p class="text-sm text-zinc-500">
                Low Stock Threshold
            </p>

            <p class="mt-2 text-3xl font-bold">
                {{ $lowStockLimit }}
            </p>

            <p class="mt-2 text-xs text-zinc-500">
                This is currently a fixed threshold.
            </p>

        </div>

    </div>

    {{-- Variants Stock --}}
    @if($variants->isNotEmpty())

        <div class="rounded-2xl border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-900">

            <h2 class="mb-4 text-lg font-semibold">
                Variant-wise Stock
            </h2>

            <div class="grid gap-3 md:grid-cols-2 lg:grid-cols-3">

                @foreach($variants as $variant)

                    <div class="rounded-xl border border-zinc-200 p-4 dark:border-zinc-700">

                        <p class="font-semibold">
                            {{ $variant->name }}
                        </p>

                        <p class="mt-1 text-xs text-zinc-500">
                            SKU: {{ $variant->sku ?: 'N/A' }}
                        </p>

                        <p class="mt-3 text-2xl font-bold">
                            {{ $variant->stock_quantity }}
                        </p>

                        @if($variant->stock_quantity <= $lowStockLimit)
                            <span class="mt-2 inline-block rounded-lg bg-red-50 px-3 py-1 text-xs font-semibold text-red-600 dark:bg-red-950">
                                {{ $variant->stock_quantity == 0 ? 'Out of Stock' : 'Low Stock' }}
                            </span>
                        @else
                            <span class="mt-2 inline-block text-xs text-green-600">
                                In Stock
                            </span>
                        @endif

                    </div>

                @endforeach

            </div>
        </div>

    @endif

    {{-- Stock Management --}}
    @if($canEdit)

        <form
            method="POST"
            action="{{ route('businesses.products.inventory.store', [$business, $product]) }}"
            class="space-y-5 rounded-2xl border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-900"
        >
            @csrf

            <div>
                <h2 class="text-lg font-semibold">
                    Manage Stock
                </h2>

                <p class="mt-1 text-sm text-zinc-500">
                    Add, remove or adjust stock quantities.
                </p>
            </div>

            <div class="grid gap-5 md:grid-cols-2">

                <label class="space-y-1">
                    <span class="text-sm font-medium">
                        Stock Target *
                    </span>

                    <select
                        name="stock_target"
                        id="inventory-target"
                        required
                        class="w-full rounded-xl border border-zinc-300 bg-white px-3 py-2.5 dark:border-zinc-700 dark:bg-zinc-800"
                    >
                        <option value="product"
                            @selected(old('stock_target', 'product') === 'product')>
                            Main Product
                        </option>

                        @if($variants->isNotEmpty())
                            <option value="variant"
                                @selected(old('stock_target') === 'variant')>
                                Product Variant
                            </option>
                        @endif
                    </select>
                </label>

                <label
                    id="inventory-variant-wrapper"
                    class="space-y-1"
                >
                    <span class="text-sm font-medium">
                        Select Variant
                    </span>

                    <select
                        name="variant_id"
                        id="inventory-variant"
                        class="w-full rounded-xl border border-zinc-300 bg-white px-3 py-2.5 dark:border-zinc-700 dark:bg-zinc-800"
                    >
                        <option value="">Select Variant</option>

                        @foreach($variants as $variant)
                            <option
                                value="{{ $variant->id }}"
                                @selected((string) old('variant_id') === (string) $variant->id)
                            >
                                {{ $variant->name }}
                                (Stock: {{ $variant->stock_quantity }})
                            </option>
                        @endforeach
                    </select>
                </label>

                <label class="space-y-1">
                    <span class="text-sm font-medium">
                        Stock Action *
                    </span>

                    <select
                        name="movement_type"
                        id="inventory-movement-type"
                        required
                        class="w-full rounded-xl border border-zinc-300 bg-white px-3 py-2.5 dark:border-zinc-700 dark:bg-zinc-800"
                    >
                        <option value="stock_in"
                            @selected(old('movement_type') === 'stock_in')>
                            Stock In (+)
                        </option>

                        <option value="stock_out"
                            @selected(old('movement_type') === 'stock_out')>
                            Stock Out (-)
                        </option>

                        <option value="adjustment"
                            @selected(old('movement_type') === 'adjustment')>
                            Set Actual Stock
                        </option>
                    </select>
                </label>

                <label class="space-y-1">
                    <span
                        id="inventory-quantity-label"
                        class="text-sm font-medium"
                    >
                        Quantity *
                    </span>

                    <input
                        type="number"
                        name="quantity"
                        id="inventory-quantity"
                        min="0"
                        max="2147483647"
                        required
                        value="{{ old('quantity', 1) }}"
                        class="w-full rounded-xl border border-zinc-300 bg-white px-3 py-2.5 dark:border-zinc-700 dark:bg-zinc-800"
                    >
                </label>

            </div>

            <label class="block space-y-1">
                <span class="text-sm font-medium">
                    Reason / Note *
                </span>

                <input
                    type="text"
                    name="reason"
                    maxlength="255"
                    required
                    value="{{ old('reason') }}"
                    placeholder="Example: New stock received, damaged item, physical stock correction"
                    class="w-full rounded-xl border border-zinc-300 bg-white px-3 py-2.5 dark:border-zinc-700 dark:bg-zinc-800"
                >
            </label>

            <button
                type="submit"
                class="rounded-xl bg-zinc-950 px-6 py-2.5 font-semibold text-white dark:bg-white dark:text-zinc-950"
            >
                Save Stock Movement
            </button>

        </form>

    @endif

    {{-- Stock History --}}
    <div class="space-y-4">

        <h2 class="text-lg font-semibold">
            Stock Movement History
        </h2>

        <div class="overflow-x-auto rounded-2xl border border-zinc-200 dark:border-zinc-700">

            <table class="w-full text-left text-sm">

                <thead class="bg-zinc-100 dark:bg-zinc-800">
                    <tr>
                        <th class="p-4">Date</th>
                        <th class="p-4">Product / Variant</th>
                        <th class="p-4">Action</th>
                        <th class="p-4">Change</th>
                        <th class="p-4">Before</th>
                        <th class="p-4">After</th>
                        <th class="p-4">Updated By</th>
                        <th class="p-4">Reason</th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">

                    @forelse($movements as $movement)

                        <tr>
                            <td class="whitespace-nowrap p-4">
                                {{ $movement->created_at->format('d M Y, h:i A') }}
                            </td>

                            <td class="p-4">
                                {{ $movement->variant?->name ?? 'Main Product' }}
                            </td>

                            <td class="p-4">
                                {{ str_replace('_', ' ', ucfirst($movement->movement_type)) }}
                            </td>

                            <td class="p-4">
                                <span class="{{ $movement->quantity_change > 0 ? 'text-green-600' : 'text-red-600' }} font-semibold">
                                    {{ $movement->quantity_change > 0 ? '+' : '' }}{{ $movement->quantity_change }}
                                </span>
                            </td>

                            <td class="p-4">
                                {{ $movement->quantity_before }}
                            </td>

                            <td class="p-4 font-semibold">
                                {{ $movement->quantity_after }}
                            </td>

                            <td class="p-4">
                                {{ $movement->user?->name ?? 'System' }}
                            </td>

                            <td class="p-4">
                                {{ $movement->reason }}
                            </td>
                        </tr>

                    @empty

                        <tr>
                            <td colspan="8" class="p-8 text-center text-zinc-500">
                                No stock movements found.
                            </td>
                        </tr>

                    @endforelse

                </tbody>

            </table>
        </div>

        {{ $movements->links() }}
    </div>

</div>

<script>
(function () {

    function initializeInventoryForm() {

        const target = document.getElementById('inventory-target');
        const variantWrapper = document.getElementById(
            'inventory-variant-wrapper'
        );
        const variant = document.getElementById('inventory-variant');
        const movement = document.getElementById('inventory-movement-type');
        const quantity = document.getElementById('inventory-quantity');
        const quantityLabel = document.getElementById(
            'inventory-quantity-label'
        );

        if (!target || target.dataset.initialized === '1') {
            return;
        }

        target.dataset.initialized = '1';

        function updateTarget() {
            const isVariant = target.value === 'variant';

            variantWrapper.hidden = !isVariant;
            variant.disabled = !isVariant;
            variant.required = isVariant;

            if (!isVariant) {
                variant.value = '';
            }
        }

        function updateMovement() {
            const isAdjustment = movement.value === 'adjustment';

            quantity.min = isAdjustment ? '0' : '1';

            quantityLabel.textContent = isAdjustment
                ? 'Actual Stock Quantity *'
                : 'Change Quantity *';
        }

        target.addEventListener('change', updateTarget);
        movement.addEventListener('change', updateMovement);

        updateTarget();
        updateMovement();
    }

    if (document.readyState === 'loading') {
        document.addEventListener(
            'DOMContentLoaded',
            initializeInventoryForm,
            { once: true }
        );
    } else {
        initializeInventoryForm();
    }

    document.addEventListener(
        'livewire:navigated',
        initializeInventoryForm
    );

})();
</script>

</x-layouts::app>
