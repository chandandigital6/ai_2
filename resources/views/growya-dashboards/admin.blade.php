<x-layouts::app :title="__('Platform overview')">
<div class="g-page">
    <div class="g-page-heading"><div><p class="g-kicker">Platform overview</p><h1>Good to see you, {{ auth()->user()->name }}.</h1><p>Everything happening across your Growya marketplace.</p></div><div class="g-actions"><a href="{{ route('growya.roles.matrix') }}" class="g-btn g-btn-secondary"><x-growya.icon name="shield" />Access control</a><a href="{{ route('admin.business-verifications.index') }}" class="g-btn g-btn-primary">Review applications<x-growya.icon name="arrow-right" /></a></div></div>
    <section class="g-hero"><div><p class="g-kicker"><x-growya.icon name="shield" />Your marketplace, at a glance</p><h2>A stronger local business community.</h2><p>Keep your marketplace trusted. Review new businesses, manage access and stay close to order activity.</p><a href="{{ route('admin.business-verifications.index') }}" class="g-btn g-btn-white">{{ number_format($pendingCount) }} pending {{ $pendingCount === 1 ? 'application' : 'applications' }}<x-growya.icon name="arrow-right" /></a></div><div class="g-hero-figure" aria-hidden="true"><x-growya.icon name="store" /><span class="g-hero-figure-badge">GROW TOGETHER</span></div></section>
    <section class="g-stats" aria-label="Marketplace statistics">
        <x-growya.stat label="Total businesses" :value="number_format($businessesCount)" note="All registered businesses" icon="store" :href="route('businesses.index')" />
        <x-growya.stat label="Registered users" :value="number_format($usersCount)" note="Accounts on Growya" icon="users" tone="blue" :href="route('users.index')" />
        <x-growya.stat label="Marketplace orders" :value="number_format($ordersCount)" note="All order statuses" icon="bag" tone="violet" />
        <x-growya.stat label="Paid order value" :value="'₹' . number_format((float) $revenueTotal, 2)" note="Paid orders, excluding cancelled" icon="wallet" tone="amber" />
    </section>
    <div class="g-split">
        <section class="g-panel"><div class="g-panel-head"><div><h2>Business applications</h2><p>New registrations waiting for your review</p></div><a class="g-link" href="{{ route('admin.business-verifications.index') }}">View all<x-growya.icon name="arrow-right" /></a></div><div class="g-panel-body">
            @forelse($latestPending as $item)
                <div class="g-row"><div class="g-row-main"><span class="g-avatar">{{ mb_strtoupper(mb_substr($item->name, 0, 2)) }}</span><div><p class="g-row-title">{{ $item->name }}</p><p class="g-row-subtitle">{{ $item->owner?->name ?? 'Owner' }} · {{ $item->created_at?->format('d M Y') }}</p></div></div><a class="g-btn g-btn-secondary g-btn-small" href="{{ route('admin.business-verifications.show', $item) }}">Review<x-growya.icon name="arrow-up-right" /></a></div>
            @empty<x-growya.empty title="All caught up" description="New business applications will appear here for review." icon="shield" />@endforelse
        </div></section>
        <section class="g-panel"><div class="g-panel-head"><div><h2>Business verification</h2><p>Current registration status</p></div><span class="g-icon-box"><x-growya.icon name="shield" /></span></div><div class="g-panel-body">
            @foreach([['Approved', $approvedCount, 'green'], ['Pending review', $pendingCount, 'amber'], ['Rejected', $rejectedCount, 'rose']] as [$label, $count, $tone])
                <div style="margin-top:18px"><div class="flex items-center justify-between text-xs"><span class="g-muted">{{ $label }}</span><strong>{{ number_format($count) }}</strong></div><div class="g-progress"><span class="g-progress-{{ $tone }}" style="width:{{ $businessesCount ? min(100, round($count / $businessesCount * 100)) : 0 }}%"></span></div></div>
            @endforeach
            <p class="g-row-subtitle" style="margin-top:20px">Based on {{ number_format($businessesCount) }} registered businesses.</p>
            <a class="g-link" style="margin-top:18px" href="{{ route('businesses.index') }}">Manage all businesses<x-growya.icon name="arrow-right" /></a>
        </div></section>
    </div>
    <div class="g-split">
        <section class="g-panel"><div class="g-panel-head"><div><h2>Latest marketplace orders</h2><p>Recent activity across all businesses</p></div><span class="g-badge g-tone-green">{{ number_format($ordersCount) }} total orders</span></div><div class="g-table-wrap"><table class="g-table"><thead><tr><th>Order</th><th>Business</th><th>Status</th><th>Amount</th><th>Date</th></tr></thead><tbody>
            @forelse($latestOrders as $order)<tr><td>@if($order->business)<a href="{{ route('businesses.orders.show', [$order->business, $order]) }}">{{ $order->order_number ?? '#' . $order->id }}</a>@else{{ $order->order_number ?? '#' . $order->id }}@endif</td><td>{{ $order->business?->name ?? 'Business unavailable' }}</td><td><x-growya.status :value="$order->status" /></td><td><strong>₹{{ number_format((float) $order->total_amount, 2) }}</strong></td><td>{{ $order->created_at?->format('d M') }}</td></tr>@empty<tr><td colspan="5"><x-growya.empty title="No orders yet" description="Marketplace orders will appear here once customers start shopping." icon="bag" /></td></tr>@endforelse
        </tbody></table></div></section>
        <section class="g-panel"><div class="g-panel-head"><div><h2>Quick management</h2><p>Your platform essentials</p></div></div><div class="g-quick-grid">
            @foreach([['Users', 'users.index', 'users'], ['User roles', 'growya.users.roles.index', 'shield'], ['Categories', 'categories.index', 'grid'], ['Ownership', 'growya.ownership.index', 'return'], ['Permissions', 'growya.roles.matrix', 'settings'], ['Reviews', 'admin.business-reviews.index', 'star']] as [$label, $routeName, $icon])<a class="g-quick" href="{{ route($routeName) }}"><span class="g-icon-box"><x-growya.icon :name="$icon" /></span>{{ $label }}</a>@endforeach
        </div></section>
    </div>
</div>
</x-layouts::app>
