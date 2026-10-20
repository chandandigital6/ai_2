<x-layouts::app title="Product Enquiries">
    <div class="max-w-7xl mx-auto p-6 space-y-5">
        <h1 class="text-2xl font-bold">Product Enquiries — {{ $business->name }}</h1><a
            href="{{ route('businesses.index') }}" class="text-blue-600">← Businesses</a>
        <form method="GET" class="flex flex-wrap gap-3"><input name="search" value="{{ request('search') }}"
                placeholder="Name / phone / product" class="border rounded p-2"><select name="status"
                class="border rounded p-2">
                <option value="">All statuses</option>
                @foreach (['new', 'contacted', 'converted', 'closed'] as $s)
                    <option value="{{ $s }}" @selected(request('status') === $s)>{{ ucfirst($s) }}</option>
                @endforeach
            </select>
            <button class="bg-emerald-700 text-white rounded px-4">Search</button>
        </form>
        <div class="overflow-x-auto border rounded-xl">
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-left border-b">
                        <th class="p-3">Customer</th>
                        <th class="p-3">Product</th>
                        <th class="p-3">Qty</th>
                        <th class="p-3">Status</th>
                        <th class="p-3">Date</th>
                        <th class="p-3">Details</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($enquiries as $e)
                        <tr class="border-b">
                            <td class="p-3">{{ $e->name }}<br><a href="tel:{{ $e->phone }}"
                                    class="text-blue-600">{{ $e->phone }}</a></td>
                            <td class="p-3">{{ $e->product?->name ?? 'Removed product' }}</td>
                            <td class="p-3">{{ $e->quantity ?? '—' }}</td>
                            <td class="p-3">{{ ucfirst($e->status) }}</td>
                            <td class="p-3">{{ $e->created_at?->format('d M Y') }}</td>
                            <td class="p-3"><a class="text-blue-600"
                                    href="{{ route('businesses.product-enquiries.show', [$business, $e]) }}">View</a>
                            </td>
                    </tr>@empty<tr>
                            <td colspan="6" class="p-6 text-center">No enquiries yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>{{ $enquiries->links() }}
    </div>
</x-layouts::app>
