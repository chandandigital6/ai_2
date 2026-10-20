<x-layouts::app title="Offer Editor">
    @php
        $editing = $offer->exists;
        $selectedProducts = old('product_ids', $selectedProducts);
        $selectedServices = old('service_ids', $selectedServices);
    @endphp
    <div class="mx-auto max-w-4xl space-y-5 p-4 md:p-6">
        <h1 class="text-2xl font-bold">{{ $editing ? 'Edit Offer' : 'Create Offer' }} — {{ $business->name }}</h1>
        @if ($errors->any())
            <div class="rounded-lg bg-red-100 p-4 text-red-800">
                <ul class="list-disc pl-5">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif
        <form enctype="multipart/form-data" method="POST"
            action="{{ $editing ? route('businesses.offers.update', [$business, $offer]) : route('businesses.offers.store', $business) }}"
            class="space-y-5 rounded-xl border p-5">@csrf @if ($editing)
                @method('PUT')
            @endif
            <div><label class="mb-1 block font-medium">Offer Title *</label><input
                    class="w-full rounded-lg border p-3 dark:bg-zinc-800" name="title" required maxlength="255"
                    value="{{ old('title', $offer->title) }}"></div>
            <div><label class="mb-1 block font-medium">Description</label>
                <textarea class="w-full rounded-lg border p-3 dark:bg-zinc-800" rows="4" maxlength="5000" name="description">{{ old('description', $offer->description) }}</textarea>
            </div>
            <div class="grid gap-4 sm:grid-cols-2">
                <div><label class="mb-1 block font-medium">Offer Type *</label><select name="offer_type"
                        class="w-full rounded-lg border p-3 dark:bg-zinc-800">
                        <option value="percentage" @selected(old('offer_type', $offer->offer_type) === 'percentage')>Percentage discount</option>
                        <option value="fixed" @selected(old('offer_type', $offer->offer_type) === 'fixed')>Fixed amount discount</option>
                        <option value="announcement" @selected(old('offer_type', $offer->offer_type) === 'announcement')>Promotional announcement</option>
                    </select></div>
                <div><label class="mb-1 block font-medium">Discount Value (if discount)</label><input
                        class="w-full rounded-lg border p-3 dark:bg-zinc-800" type="number" step="0.01"
                        min="0" name="discount_value" value="{{ old('discount_value', $offer->discount_value) }}">
                </div>
                <div><label class="mb-1 block font-medium">Start Date (optional)</label><input
                        class="w-full rounded-lg border p-3 dark:bg-zinc-800" type="date" name="starts_at"
                        value="{{ old('starts_at', $offer->starts_at?->format('Y-m-d')) }}"></div>
                <div><label class="mb-1 block font-medium">End Date (optional)</label><input
                        class="w-full rounded-lg border p-3 dark:bg-zinc-800" type="date" name="ends_at"
                        value="{{ old('ends_at', $offer->ends_at?->format('Y-m-d')) }}"></div>
            </div>
            <div><label class="mb-1 block font-medium">Banner (JPG/PNG/WebP up to 5 MB)</label>
                @if ($offer->banner)
                    <img class="mb-2 max-h-36 rounded-lg object-contain"
                        src="{{ asset('storage/' . $offer->banner) }}"
                        alt="Offer banner">
                @endif
                <input type="file" name="banner_file" accept="image/jpeg,image/png,image/webp"
                    class="w-full rounded-lg border p-3">
            </div>
            <div class="grid gap-4 sm:grid-cols-2">
                <div><label class="mb-1 block font-medium">Link Products (optional, multiple)</label><select
                        name="product_ids[]" multiple size="8"
                        class="w-full rounded-lg border p-3 dark:bg-zinc-800">
                        @foreach ($products as $product)
                            <option value="{{ $product->id }}" @selected(in_array($product->id, array_map('intval', (array) $selectedProducts), true))>{{ $product->name }}
                            </option>
                        @endforeach
                    </select>
                    <p class="text-xs text-zinc-500">Hold Ctrl to choose multiple.</p>
                </div>
                <div><label class="mb-1 block font-medium">Link Services (optional, multiple)</label><select
                        name="service_ids[]" multiple size="8"
                        class="w-full rounded-lg border p-3 dark:bg-zinc-800">
                        @foreach ($services as $service)
                            <option value="{{ $service->id }}" @selected(in_array($service->id, array_map('intval', (array) $selectedServices), true))>{{ $service->name }}
                            </option>
                        @endforeach
                    </select></div>
            </div>
            <div><label class="mb-1 block font-medium">Status *</label><select name="status"
                    class="rounded-lg border p-3 dark:bg-zinc-800">
                    <option value="active" @selected(old('status', $offer->status ?? 'active') === 'active')>Active</option>
                    <option value="inactive" @selected(old('status', $offer->status) === 'inactive')>Inactive</option>
                </select></div>
            <p class="rounded-lg bg-amber-50 p-3 text-sm text-amber-900">This is a promotional offer display only.
                Product prices, service prices and checkout totals are not automatically changed.</p>
            <div class="flex gap-3"><button
                    class="rounded-lg bg-emerald-700 px-5 py-3 text-white">{{ $editing ? 'Update Offer' : 'Create Offer' }}</button><a
                    class="rounded-lg border px-5 py-3"
                    href="{{ route('businesses.offers.index', $business) }}">Cancel</a></div>
        </form>
    </div>
</x-layouts::app>
