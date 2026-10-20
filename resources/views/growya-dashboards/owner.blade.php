<x-layouts::app :title="__('Business overview')" :business="$business" :workspace-role="$workspaceRole">
    <div class="g-page">
        <div class="g-page-heading">
            <div>
                <p class="g-kicker">{{ $workspaceRole }} workspace</p>
                <h1>Business overview</h1>
                <p>Welcome, {{ auth()->user()->name }}. Here’s how your business is doing.</p>
            </div>
            <div class="g-actions">
                @if ($businesses->count() > 1)
                    <form method="GET" action="{{ route('dashboard') }}" class="g-selector"><x-growya.icon
                            name="store" /><label for="business-selector" class="sr-only">Current business</label><select
                            id="business-selector" name="business_id" onchange="this.form.submit()">
                            @foreach ($businesses as $item)
                                <option value="{{ $item->id }}" @selected($business->id === $item->id)>{{ $item->name }}
                                </option>
                            @endforeach
                        </select>
                        <noscript><button type="submit">Switch</button></noscript>
                    </form>
                @endif
                <a class="g-btn g-btn-secondary" href="{{ route('public.business.home', $business->slug) }}">View
                    showroom<x-growya.icon name="arrow-up-right" /></a>
            </div>
        </div>
        <section class="g-hero">
            <div>
                <p class="g-kicker"><x-growya.icon name="store" />Your digital showroom</p>
                <h2>{{ $business->name }}</h2>
                <p>{{ $business->city?->name ?? 'Your local business' }} · Approved on Growya</p>
                <div class="g-actions">
                    @if ($canOverview)
                        <a class="g-btn g-btn-white" href="{{ route('businesses.show', $business) }}">Manage
                            business<x-growya.icon name="arrow-right" /></a>
                        @endif @if ($canProducts)
                            <a class="g-btn g-btn-glass"
                                href="{{ route('businesses.products.index', $business) }}">Manage products<x-growya.icon
                                    name="box" /></a>
                        @elseif($canServices)
                            <a class="g-btn g-btn-glass"
                                href="{{ route('businesses.services.index', $business) }}">Manage
                                services<x-growya.icon name="sparkles" /></a>
                        @endif
                </div>
            </div>
            <div>
                <div class="g-hero-metrics">
                    <div><strong>{{ number_format($stats['views']) }}</strong><span>Profile views</span></div>
                    <div><strong>{{ number_format($stats['followers']) }}</strong><span>Followers</span></div>
                </div>
            </div>
        </section>
        <section class="g-stats" aria-label="Business statistics">
            @if ($canProducts)
                <x-growya.stat label="Products" :value="number_format($stats['products'])" note="In your product catalogue" icon="box"
                    :href="route('businesses.products.index', $business)" />
            @endif
            @if ($canServices)
                <x-growya.stat label="Services" :value="number_format($stats['services'])" note="In your service catalogue" icon="sparkles"
                    tone="blue" :href="route('businesses.services.index', $business)" />
            @endif
            @if ($canOrders)
                <x-growya.stat label="Total orders" :value="number_format($stats['orders'])" note="All orders for this business" icon="bag"
                    tone="violet" :href="route('businesses.orders.index', $business)" /><x-growya.stat label="Pending orders" :value="number_format($stats['pendingOrders'])"
                    note="Waiting for your attention" icon="clock" tone="amber" :href="route('businesses.orders.index', [$business, 'status' => 'pending'])" />
            @endif
            @if (!$canProducts && !$canServices)
                <x-growya.stat label="Profile views" :value="number_format($stats['views'])" note="Showroom visits"
                    icon="globe" /><x-growya.stat label="Followers" :value="number_format($stats['followers'])" note="Your business community"
                    icon="heart" tone="rose" />
            @endif
        </section>
        @if ($stats['sales'] !== null || $stats['enquiries'] !== null || $stats['appointments'] !== null)
            <section class="g-stats g-stats-three">
                @if ($stats['sales'] !== null)
                    <x-growya.stat label="Paid order value" :value="'₹' . number_format((float) $stats['sales'], 2)"
                        note="Paid, non-cancelled orders · not a payout balance" icon="wallet" />
                @endif
                @if ($stats['enquiries'] !== null)
                    <x-growya.stat label="Service enquiries" :value="number_format($stats['enquiries'])" note="Enquiries for this business"
                        icon="chat" tone="blue" :href="route('businesses.service-enquiries.index', $business)" />
                @endif
                @if ($stats['appointments'] !== null)<x-growya.stat label="Upcoming appointments"
                        :value="number_format($stats['appointments'])" note="Pending or confirmed · today onwards" icon="calendar" tone="amber"
                        :href="route('businesses.appointments.index', $business)" />
                @endif
            </section>
        @endif
        <div class="g-split">
            @if ($canOrders)
                <section class="g-panel">
                    <div class="g-panel-head">
                        <div>
                            <h2>Recent orders</h2>
                            <p>Your latest customer purchases</p>
                        </div><a class="g-link" href="{{ route('businesses.orders.index', $business) }}">View
                            all<x-growya.icon name="arrow-right" /></a>
                    </div>
                    <div class="g-table-wrap">
                        <table class="g-table">
                            <thead>
                                <tr>
                                    <th>Order</th>
                                    <th>Status</th>
                                    <th>Amount</th>
                                    <th>Date</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($recentOrders as $order)
                                    <tr>
                                        <td>{{ $order->order_number ?? '#' . $order->id }}</td>
                                        <td><x-growya.status :value="$order->status" /></td>
                                        <td><strong>₹{{ number_format((float) $order->total_amount, 2) }}</strong></td>
                                        <td>{{ $order->created_at?->format('d M') }}</td>
                                        <td><a href="{{ route('businesses.orders.show', [$business, $order]) }}"
                                                class="g-link"
                                                aria-label="View order {{ $order->order_number ?? $order->id }}"><x-growya.icon
                                                    name="arrow-up-right" /></a></td>
                                </tr>@empty<tr>
                                        <td colspan="5"><x-growya.empty title="Ready for your first order"
                                                description="Customer orders will appear here as they arrive."
                                                icon="bag" /></td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </section>
            @endif
            <section class="g-panel">
                <div class="g-panel-head">
                    <div>
                        <h2>Quick actions</h2>
                        <p>Tools available in your workspace</p>
                    </div>
                </div>
                <div class="g-quick-grid">
                    @foreach ([['Products', 'businesses.products.index', 'products.view', 'box'], ['Services', 'businesses.services.index', 'services.view', 'sparkles'], ['Orders', 'businesses.orders.index', 'orders.view', 'bag'], ['Payments', 'businesses.payments.index', 'payments.view', 'wallet'], ['Offers', 'businesses.offers.index', 'offers.view', 'tag'], ['Gallery', 'businesses.gallery.index', 'gallery.view', 'photo'], ['Appointments', 'businesses.appointments.index', 'appointments.view', 'calendar'], ['Product leads', 'businesses.product-enquiries.index', 'product_enquiries.view', 'chat'], ['Service leads', 'businesses.service-enquiries.index', 'service_enquiries.view', 'chat']] as [$label, $routeName, $permission, $icon])
                        @if (app(\App\Services\BusinessAccessService::class)->can(auth()->user(), $business, $permission))
                            <a class="g-quick" href="{{ route($routeName, $business) }}"><span
                                    class="g-icon-box"><x-growya.icon
                                        :name="$icon" /></span>{{ $label }}</a>
                        @endif
                    @endforeach
                    @if ($canStaff)
                        <a class="g-quick" href="{{ route('businesses.staff.index', $business) }}"><span
                                class="g-icon-box"><x-growya.icon name="users" /></span>Staff</a>
                    @endif
                    @if ($canOverview)
                        <a class="g-quick" href="{{ route('businesses.show', $business) }}"><span
                                class="g-icon-box"><x-growya.icon name="store" /></span>Business details</a>
                    @endif
                </div>
            </section>
        </div>
        @if ($canServices)
            <div class="g-columns">
                <section class="g-panel">
                    <div class="g-panel-head">
                        <div>
                            <h2>Recent enquiries</h2>
                            <p>Customers interested in your services</p>
                        </div><a class="g-link"
                            href="{{ route('businesses.service-enquiries.index', $business) }}">View all<x-growya.icon
                                name="arrow-right" /></a>
                    </div>
                    <div class="g-panel-body">
                        @forelse($recentEnquiries as $item)
                            <div class="g-row">
                                <div class="g-row-main"><span class="g-icon-box g-tone-blue"><x-growya.icon
                                            name="chat" /></span>
                                    <div>
                                        <p class="g-row-title">{{ $item->service?->name ?? 'Service enquiry' }}</p>
                                        <p class="g-row-subtitle">{{ $item->customer_name ?? 'Customer' }} ·
                                            {{ $item->created_at?->format('d M') }}</p>
                                    </div>
                                </div><a
                                    href="{{ route('businesses.service-enquiries.show', [$business, $item]) }}"><x-growya.status
                                        :value="$item->status" /></a>
                        </div>@empty<x-growya.empty title="No enquiries yet"
                                description="New service enquiries will appear here." icon="chat" />
                        @endforelse
                    </div>
                </section>
                <section class="g-panel">
                    <div class="g-panel-head">
                        <div>
                            <h2>Upcoming appointments</h2>
                            <p>Keep your next bookings in view</p>
                        </div><a class="g-link" href="{{ route('businesses.appointments.index', $business) }}">View
                            all<x-growya.icon name="arrow-right" /></a>
                    </div>
                    <div class="g-panel-body">
                        @forelse($upcomingAppointments as $item)
                            <div class="g-row">
                                <div class="g-row-main"><span
                                        class="g-avatar">{{ $item->appointment_date?->format('d') }}</span>
                                    <div>
                                        <p class="g-row-title">{{ $item->service?->name ?? 'Appointment' }}</p>
                                        <p class="g-row-subtitle">{{ $item->appointment_date?->format('d M Y') }} ·
                                            {{ substr((string) $item->appointment_time, 0, 5) }} ·
                                            {{ $item->customer_name }}</p>
                                    </div>
                                </div><a href="{{ route('businesses.appointments.show', [$business, $item]) }}"
                                    class="g-link" aria-label="View appointment"><x-growya.icon
                                        name="arrow-up-right" /></a>
                        </div>@empty<x-growya.empty title="Your schedule is clear"
                                description="Upcoming pending and confirmed appointments will appear here."
                                icon="calendar" />
                        @endforelse
                    </div>
                </section>
            </div>
        @endif
    </div>
</x-layouts::app>
