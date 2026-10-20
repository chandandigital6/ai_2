<x-layouts::app title="Coupon">
    <div class="mx-auto max-w-2xl space-y-5 p-5">
        <h1 class="text-2xl font-bold">{{ $coupon->exists ? 'Edit' : 'Create' }} Coupon — {{ $business->name }}</h1>
        @if ($errors->any())
            <div class="rounded bg-red-100 p-4 text-red-800">
                @foreach ($errors->all() as $error)
                    <p>{{ $error }}</p>
                @endforeach
            </div>
        @endif
        <form class="grid gap-4 rounded border p-5" method="POST"
            action="{{ $coupon->exists ? route('businesses.coupons.update', [$business, $coupon]) : route('businesses.coupons.store', $business) }}">
            @csrf @if ($coupon->exists)
                @method('PATCH')
            @endif
            <label>Coupon Code <input required maxlength="40" pattern="[A-Za-z0-9_-]+" name="code"
                    value="{{ old('code', $coupon->code) }}" class="block w-full rounded border p-3"></label>
            <label>Discount Type<select name="discount_type" class="block w-full rounded border p-3">
                    <option value="fixed" @selected(old('discount_type', $coupon->discount_type) === 'fixed')>Fixed ₹</option>
                    <option value="percent" @selected(old('discount_type', $coupon->discount_type) === 'percent')>Percentage %</option>
                </select></label>
            @foreach (['discount_value' => 'Discount Value', 'minimum_subtotal' => 'Minimum Subtotal ₹', 'maximum_discount' => 'Maximum Discount ₹ (optional)', 'usage_limit' => 'Overall Usage Limit (optional)', 'per_user_limit' => 'Per Customer Usage Limit'] as $field => $label)
                <label>{{ $label }}<input type="number" min="0"
                        step="{{ in_array($field, ['usage_limit', 'per_user_limit']) ? '1' : '0.01' }}"
                        name="{{ $field }}" value="{{ old($field, $coupon->$field) }}"
                        @if (in_array($field, ['discount_value', 'minimum_subtotal', 'per_user_limit'])) required @endif
                        class="block w-full rounded border p-3"></label>
            @endforeach
            <label>Starts At (optional)<input type="datetime-local" name="starts_at"
                    value="{{ old('starts_at', $coupon->starts_at?->format('Y-m-d\TH:i')) }}"
                    class="block w-full rounded border p-3"></label>
            <label>Expires At (optional)<input type="datetime-local" name="ends_at"
                    value="{{ old('ends_at', $coupon->ends_at?->format('Y-m-d\TH:i')) }}"
                    class="block w-full rounded border p-3"></label>
            <label>Active<select name="is_active" class="block w-full rounded border p-3">
                    <option value="1" @selected(old('is_active', $coupon->is_active ?? true))>Yes</option>
                    <option value="0" @selected(!old('is_active', $coupon->is_active ?? true))>No</option>
                </select></label>
            <button class="rounded bg-emerald-700 p-3 font-semibold text-white">Save Coupon</button>
        </form><a href="{{ route('businesses.coupons.index', $business) }}" class="underline">Back to Coupons</a>
    </div>
</x-layouts::app>
