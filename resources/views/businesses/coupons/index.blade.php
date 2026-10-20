<x-layouts::app title="Manage coupons">
    <div class="mx-auto max-w-5xl space-y-5 p-5">
        <div class="flex justify-between items-center">
            <h1 class="text-2xl font-bold">Coupons — {{ $business->name }}</h1><a
                class="rounded bg-emerald-700 px-4 py-2 text-white"
                href="{{ route('businesses.coupons.create', $business) }}">Create Coupon</a>
        </div>
        @if (session('success'))
            <p class="rounded bg-emerald-100 p-3">{{ session('success') }}</p>
        @endif
        <div class="overflow-x-auto rounded border">
            <table class="min-w-full text-sm">
                <thead>
                    <tr class="bg-zinc-100 text-left">
                        <th class="p-3">Code</th>
                        <th class="p-3">Discount</th>
                        <th class="p-3">Uses</th>
                        <th class="p-3">Valid until</th>
                        <th class="p-3">Active</th>
                        <th class="p-3">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($coupons as $coupon)
                        <tr class="border-t">
                            <td class="p-3 font-semibold">{{ $coupon->code }}</td>
                            <td class="p-3">
                                {{ $coupon->discount_type === 'percent' ? $coupon->discount_value . '%' : '₹' . $coupon->discount_value }}
                            </td>
                            <td class="p-3">{{ $coupon->used_count }} / {{ $coupon->usage_limit ?? '∞' }}</td>
                            <td class="p-3">{{ $coupon->ends_at?->format('d M Y H:i') ?? 'No expiry' }}</td>
                            <td class="p-3">{{ $coupon->is_active ? 'Yes' : 'No' }}</td>
                            <td class="p-3"><a class="underline"
                                    href="{{ route('businesses.coupons.edit', [$business, $coupon]) }}">Edit</a></td>
                    </tr>@empty<tr>
                            <td class="p-4" colspan="6">No coupons yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>{{ $coupons->links() }}
    </div>
</x-layouts::app>
