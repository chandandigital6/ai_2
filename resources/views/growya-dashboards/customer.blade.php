<x-layouts::app :title="__('My overview')">
    <div class="g-page">
        <div class="g-page-heading">
            <div>
                <p class="g-kicker">Your Growya workspace</p>
                <h1>Welcome back, {{ auth()->user()->name }}.</h1>
                <p>Your favourite businesses, purchases and applications in one place.</p>
            </div><a class="g-btn g-btn-secondary" href="{{ route('profile.edit') }}"><x-growya.icon name="settings" />Edit
                profile</a>
        </div>
        <section class="g-hero">
            <div>
                <p class="g-kicker"><x-growya.icon name="sparkles" />Discover something local</p>
                <h2>Great businesses. Right around you.</h2>
                <p>Explore local stores, find products you love and connect with service providers. Your next discovery
                    starts here.</p>
                <div class="g-actions"><a class="g-btn g-btn-white"
                        href="{{ route('public.marketplace.index') }}">Explore marketplace<x-growya.icon
                            name="arrow-right" /></a><a class="g-btn g-btn-glass"
                        href="{{ route('public.offers.index') }}">Browse offers<x-growya.icon name="tag" /></a>
                </div>
            </div>
            <div class="g-hero-figure" aria-hidden="true"><x-growya.icon name="bag" /><span
                    class="g-hero-figure-badge">DISCOVER LOCAL</span></div>
        </section>
        <section class="g-stats g-stats-three" aria-label="Your activity">
            <x-growya.stat label="My orders" :value="number_format($ordersCount)" note="Purchases and delivery history" icon="bag"
                :href="route('growya.orders.mine')" />
            <x-growya.stat label="Following" :value="number_format($followingCount)" note="Businesses you keep close" icon="heart"
                tone="rose" :href="route('public.business.following')" />
            <x-growya.stat label="My reviews" :value="number_format($reviewsCount)" note="Ratings you have shared" icon="star"
                tone="amber" />
        </section>
        <div class="g-split">
            <section class="g-panel">
                <div class="g-panel-head">
                    <div>
                        <h2>Recent orders</h2>
                        <p>Keep track of your latest purchases</p>
                    </div><a href="{{ route('growya.orders.mine') }}" class="g-link">View all<x-growya.icon
                            name="arrow-right" /></a>
                </div>
                <div class="g-panel-body">
                    @forelse($recentOrders as $order)
                        <div class="g-row">
                            <div class="g-row-main"><span class="g-icon-box"><x-growya.icon name="bag" /></span>
                                <div>
                                    <p class="g-row-title"><a
                                            href="{{ route('growya.orders.show', $order) }}">{{ $order->order_number ?? 'Order #' . $order->id }}</a>
                                    </p>
                                    <p class="g-row-subtitle">{{ $order->business?->name ?? 'Business' }} ·
                                        {{ $order->created_at?->format('d M Y') }}</p>
                                </div>
                            </div>
                            <div class="text-right">
                                <p class="g-row-title" style="margin-bottom:5px">
                                    ₹{{ number_format((float) $order->total_amount, 2) }}</p><x-growya.status
                                    :value="$order->status" />
                            </div>
                    </div>@empty<x-growya.empty title="Your first discovery awaits"
                            description="Shop from local businesses. Your purchases will appear here." icon="bag"
                            :href="route('public.marketplace.index')" />
                    @endforelse
                </div>
            </section>
            <section class="g-panel">
                <div class="g-panel-head">
                    <div>
                        <h2>Quick links</h2>
                        <p>More ways to explore Growya</p>
                    </div>
                </div>
                <div class="g-quick-grid">
                    @foreach ([['Marketplace', 'public.marketplace.index', 'store'], ['My orders', 'growya.orders.mine', 'bag'], ['Offers', 'public.offers.index', 'tag'], ['Following', 'public.business.following', 'heart'], ['Search', 'public.search.index', 'search'], ['Notifications', 'growya.notifications.index', 'bell']] as [$label, $routeName, $icon])
                        <a class="g-quick" href="{{ route($routeName) }}"><span class="g-icon-box"><x-growya.icon
                                    :name="$icon" /></span>{{ $label }}</a>
                    @endforeach
                </div>
            </section>
        </div>
        <section class="g-panel">
            <div class="g-panel-head">
                <div>
                    <h2>Your business applications</h2>
                    <p>Take your business online and track its approval</p>
                </div><a class="g-btn g-btn-primary g-btn-small" href="{{ route('businesses.create') }}"><x-growya.icon
                        name="plus" />Register a business</a>
            </div>
            <div class="g-panel-body">
                @forelse($businesses as $item)
                    <div class="g-row">
                        <div class="g-row-main"><span
                                class="g-avatar">{{ mb_strtoupper(mb_substr($item->name, 0, 2)) }}</span>
                            <div>
                                <p class="g-row-title">{{ $item->name }}</p>
                                <p class="g-row-subtitle">Submitted {{ $item->created_at?->format('d M Y') }}</p>
                            </div>
                        </div>
                        <div class="g-actions"><x-growya.status :value="$item->verification_status" />
                            @can('update', $item)
                                <a class="g-link" href="{{ route('businesses.edit', $item) }}">Details<x-growya.icon
                                        name="arrow-up-right" /></a>
                            @endcan
                        </div>
                </div>@empty<x-growya.empty title="Have a business of your own?"
                        description="Create a digital showroom and help more customers discover your products and services."
                        icon="store" :href="route('businesses.create')" action="Create your showroom" />
                @endforelse
            </div>
        </section>
    </div>
</x-layouts::app>
