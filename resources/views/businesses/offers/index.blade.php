<x-layouts::app title="Business Offers">
    <div class="mx-auto max-w-6xl space-y-6 p-4 md:p-6">
        <div class="flex flex-wrap justify-between gap-3">
            <div>
                <h1 class="text-2xl font-bold">Offers & Promotions</h1>
                <p class="text-sm text-zinc-500">{{ $business->name }}</p>
            </div>
            <div class="flex gap-2"><a class="rounded-lg border px-4 py-2"
                    href="{{ route('businesses.index') }}">Businesses</a>
                @if (app(\App\Services\BusinessAccessService::class)->can(auth()->user(), $business, 'services.update'))
                    <a class="rounded-lg bg-emerald-700 px-4 py-2 text-white"
                        href="{{ route('businesses.offers.create', $business) }}">+ New Offer</a>
                @endif
            </div>
        </div>
        @if (session('success'))
            <div class="rounded-lg bg-green-100 p-3 text-green-800">{{ session('success') }}</div>
        @endif
        <div class="overflow-x-auto rounded-xl border">
            <table class="w-full text-left text-sm">
                <thead class="bg-zinc-100 dark:bg-zinc-800">
                    <tr>
                        <th class="p-3">Offer</th>
                        <th class="p-3">Discount</th>
                        <th class="p-3">Dates</th>
                        <th class="p-3">Visibility</th>
                        <th class="p-3">Links</th>
                        <th class="p-3">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($offers as $offer)
                        <tr class="border-t">
                            <td class="p-3 font-semibold">{{ $offer->title }}</td>
                            <td class="p-3">
                                @if ($offer->offer_type === 'percentage')
                                    {{ $offer->discount_value }}% off
                                @elseif($offer->offer_type === 'fixed')
                                    ₹{{ $offer->discount_value }} off
                                @else
                                    Announcement
                                @endif
                            </td>
                            <td class="p-3">{{ $offer->starts_at?->format('d M Y') ?? 'Anytime' }} –
                                {{ $offer->ends_at?->format('d M Y') ?? 'No end' }}</td>
                            <td class="p-3">{{ ucfirst($offer->status) }} @if ($offer->ends_at && $offer->ends_at->lt(today()))
                                    (Expired)
                                @endif
                            </td>
                            <td class="p-3">{{ $offer->offer_products_count }} products,
                                {{ $offer->offer_services_count }} services</td>
                            <td class="p-3">
                                <div class="flex gap-3">
                                    @if (app(\App\Services\BusinessAccessService::class)->can(auth()->user(), $business, 'services.update'))
                                        <a class="text-blue-600"
                                            href="{{ route('businesses.offers.edit', [$business, $offer]) }}">Edit</a>
                                        <form action="{{ route('businesses.offers.destroy', [$business, $offer]) }}"
                                            method="POST" onsubmit="return confirm('Archive this offer?')">@csrf
                                            @method('DELETE')<button class="text-red-600">Archive</button></form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty<tr>
                            <td colspan="6" class="p-8 text-center text-zinc-500">No offers yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>{{ $offers->links() }}
    </div>
</x-layouts::app>
