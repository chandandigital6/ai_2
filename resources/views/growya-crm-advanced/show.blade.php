<x-layouts::app :title="'Customer 360'">
    <div class="mx-auto max-w-6xl px-5 py-8 space-y-6">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold">Customer 360° — {{ $contact->name }}</h1>
                <p class="text-sm text-zinc-500">{{ $business->name }} · {{ ucfirst($contact->status) }} ·
                    {{ $contact->user_id ? 'Verified user link #' . $contact->user_id : 'Unlinked/manual contact' }}</p>
            </div><a class="text-emerald-700 underline" href="{{ route('growya.crm.index', $business) }}">CRM Directory</a>
        </div>
        @if (!$contact->user_id)
            <div class="rounded-xl border border-amber-300 bg-amber-50 p-4 text-amber-900">No verified User ID is linked.
                Order, chat, enquiry and wishlist history is hidden to prevent matching the wrong customer by name,
                email or phone.</div>
        @endif
        <div class="grid gap-3 md:grid-cols-3">
            @foreach ([['Orders', $orders->count()], ['Wishlist interests', $wish->count()], ['Chat threads', $chat->count()], ['Product enquiries', $productEnquiries->count()], ['Service enquiries', $serviceEnquiries->count()], ['Linked sources', $links->count()]] as $stat)
                <div class="rounded-xl border p-4 dark:border-zinc-700">
                    <p class="text-sm text-zinc-500">{{ $stat[0] }} (latest 20/40)</p><strong
                        class="text-2xl">{{ $stat[1] }}</strong>
                </div>
            @endforeach
        </div>
        @foreach ([['Recent orders', $orders, 'order_number', 'status'], ['Wishlist', $wish, 'item_type', 'status'], ['Chats', $chat, 'context_type', 'last_message_at'], ['Product enquiries', $productEnquiries, 'product_id', 'status'], ['Service enquiries', $serviceEnquiries, 'service_id', 'status'], ['CRM activity', $activities, 'type', 'description']] as $section)
            <section class="rounded-xl border p-5 dark:border-zinc-700">
                <h2 class="font-semibold text-lg mb-3">{{ $section[0] }}</h2>
                <div class="space-y-2">
                    @forelse($section[1] as $r)
                        <div class="flex justify-between border-b pb-2 gap-4 dark:border-zinc-700">
                            <span>{{ $r->{$section[2]} ?? '—' }}</span><span
                                class="text-zinc-500 text-sm">{{ \Illuminate\Support\Str::limit((string) ($r->{$section[3]} ?? '—'), 120) }}</span>
                    </div>@empty<p class="text-zinc-500">No records</p>
                    @endforelse
                </div>
            </section>
        @endforeach
        <p class="text-sm text-zinc-500">Historical records are read-only. "Converted" reflects CRM status, not a
            verified purchase.</p>
    </div>
</x-layouts::app>
