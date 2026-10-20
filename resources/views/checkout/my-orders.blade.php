<x-layouts::app title="My orders">
    <div id="main-content" class="g-page">
        <div class="g-page-heading">
            <div>
                <p class="g-kicker">Your purchases</p>
                <h1>My orders</h1>
                <p>Track purchases and delivery from your favourite local businesses.</p>
            </div>
            <a class="g-btn g-btn-primary" href="{{ route('public.marketplace.index') }}">
                Explore marketplace<x-growya.icon name="arrow-right" />
            </a>
        </div>

        @if (session('success'))
            <div class="rounded-xl bg-emerald-50 p-4 text-sm font-semibold text-emerald-800 dark:bg-emerald-950 dark:text-emerald-200" role="status">
                {{ session('success') }}
            </div>
        @endif

        <section class="g-panel">
            <div class="g-panel-head">
                <h2>Order history</h2>
                <span class="g-badge g-tone-green">{{ number_format($orders->total()) }} {{ \Illuminate\Support\Str::plural('order', $orders->total()) }}</span>
            </div>

            <div class="g-panel-body">
                @forelse ($orders as $order)
                    <div class="g-row">
                        <div class="g-row-main">
                            <span class="g-icon-box"><x-growya.icon name="bag" /></span>
                            <div>
                                <p class="g-row-title">
                                    <a href="{{ route('growya.orders.show', $order) }}">{{ $order->order_number }}</a>
                                </p>
                                <p class="g-row-subtitle">
                                    {{ $order->created_at?->format('d M Y') }}
                                    @if ($order->payment_status === 'pending' && $order->payment_method === 'upi_manual')
                                        · Payment pending
                                    @endif
                                </p>
                            </div>
                        </div>

                        <div class="g-actions">
                            <strong class="text-sm">₹{{ number_format((float) $order->total_amount, 2) }}</strong>
                            <x-growya.status :value="$order->status" />
                            <a class="g-link" aria-label="View order {{ $order->order_number }}"
                                href="{{ route('growya.orders.show', $order) }}">
                                <x-growya.icon name="arrow-up-right" />
                            </a>
                        </div>
                    </div>
                @empty
                    <x-growya.empty title="Your next discovery awaits"
                        description="Explore local showrooms. Your purchases will be listed here." icon="bag"
                        :href="route('public.marketplace.index')" />
                @endforelse
            </div>
        </section>

        {{ $orders->links() }}
    </div>
</x-layouts::app>