@props(['title' => null, 'business' => null, 'workspaceRole' => null])
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    @include('partials.head')
</head>

<body class="g-app">
    @php
        $sidebarUser = auth()->user();
        $isSuperAdmin = $sidebarUser->hasRole('super_admin', 'web');
        $sidebarBusiness = $business ?? request()->route('business');

        if ($sidebarBusiness && !($sidebarBusiness instanceof \App\Models\Business)) {
            $sidebarBusiness = \App\Models\Business::find($sidebarBusiness);
        }

        $access = app(\App\Services\BusinessAccessService::class);
        $isBusinessOwner = $sidebarBusiness && (int) $sidebarBusiness->owner_id === (int) $sidebarUser->id;
        $sidebarMembership = $sidebarBusiness ? $access->membership($sidebarUser, $sidebarBusiness) : null;
        $hasBusinessAccess = $sidebarBusiness && ($isSuperAdmin || $isBusinessOwner || $sidebarMembership);

        $sidebarRole = $isSuperAdmin
            ? 'Super Admin'
            : $workspaceRole ??
                ($isBusinessOwner ? 'Business Owner' : ($sidebarMembership ? 'Business Admin' : 'Customer'));

        $businessMenu = [
            ['Overview', 'businesses.show', 'store', 'business.view'],
            ['Customer CRM', 'growya.crm.index', 'users', 'customers.view'],
            ['Lead Insights', 'growya.lead-insights.owner', 'grid', 'leads.view'],
            ['Customer messages', 'growya.chat.inbox', 'chat', 'chats.view'],
            ['KYC & Documents', 'growya.kyc.owner', 'shield', 'documents.view'],
            ['Subscription billing', 'growya.billing.owner', 'wallet', 'billing.view'],
            ['Analytics & Reports', 'growya.analytics.owner', 'grid', 'analytics.view'],
            ['Coupons & Discounts', 'businesses.coupons.index', 'tag', 'coupons.view'],
            ['Plan usage', 'growya.entitlements.usage', 'chart', 'subscriptions.view'],
            ['Subscription plans', 'growya.subscriptions.business', 'wallet', 'subscriptions.view'],
            ['Map Location', 'growya.nearby.location.edit', 'pin', 'business.update'],
            ['Paid promotions', 'growya.promotions.owner', 'tag', 'promotions.view'],
            ['Products', 'businesses.products.index', 'box', 'products.view'],
            ['Services', 'businesses.services.index', 'sparkles', 'services.view'],
            ['Orders', 'businesses.orders.index', 'bag', 'orders.view'],
            ['Payments', 'businesses.payments.index', 'wallet', 'payments.view'],
            ['Returns & refunds', 'businesses.returns.index', 'return', 'returns.view'],
            ['Delivery management', 'businesses.delivery.index', 'truck', 'delivery.view'],
            ['Product enquiries', 'businesses.product-enquiries.index', 'chat', 'product_enquiries.view'],
            ['Service enquiries', 'businesses.service-enquiries.index', 'chat', 'service_enquiries.view'],
            ['Appointments', 'businesses.appointments.index', 'calendar', 'appointments.view'],
            ['Offers & promotions', 'businesses.offers.index', 'tag', 'offers.view'],
            ['Gallery', 'businesses.gallery.index', 'photo', 'gallery.view'],
            ['Reviews', 'businesses.reviews.index', 'star', 'reviews.view'],
            ['Business hours', 'businesses.hours.index', 'clock', 'business.hours.manage'],
            ['Social links', 'businesses.social-links.index', 'link', 'business.social.manage'],
        ];

        $adminMenu = [
            ['Business approvals', 'admin.business-verifications.index', 'admin.business-verifications.*', 'shield'],
            ['All businesses', 'businesses.index', 'businesses.index', 'store'],
            ['Lead Insights', 'growya.lead-insights.admin', 'growya.lead-insights.admin*', 'leads.view'],
            ['Referrals & Rewards', 'growya.referrals.admin', 'growya.referrals.admin', 'users'],
            ['Subscription billing', 'growya.billing.admin', 'growya.billing.admin', 'billing.view'],
            ['Chat moderation', 'growya.chat.reports', 'growya.chat.reports*', 'shield'],
            ['Business claims', 'growya.claims.admin', 'growya.claims.admin*', 'shield'],
            ['Business KYC Reviews', 'growya.kyc.admin', 'growya.kyc.admin*', 'shield'],
            ['Analytics & Reports', 'growya.analytics.admin', 'growya.analytics.admin*', 'analytics.view'],
            ['Users', 'users.index', 'users.*', 'users'],
            ['User roles', 'growya.users.roles.index', 'growya.users.roles.*', 'users'],
            ['Access control', 'growya.roles.matrix', 'growya.roles.matrix*', 'shield'],
            ['Roles', 'roles.index', 'roles.*', 'grid'],
            ['Permissions', 'permissions.index', 'permissions.*', 'shield'],
            ['Ownership transfers', 'growya.ownership.index', 'growya.ownership.*', 'return'],
            ['Categories', 'categories.index', 'categories.*', 'grid'],
            ['Product tags', 'tags.index', 'tags.*', 'tag'],
            ['Product attributes', 'attributes.index', 'attributes.*', 'settings'],
            ['Promotion approvals', 'growya.promotions.admin', 'growya.promotions.*', 'star'],
            ['Promotion packages', 'growya.promotions.packages', 'growya.promotions.packages*', 'tag'],
            ['Manage plans', 'growya.plans.index', 'growya.plans.*', 'wallet'],
            ['Subscriptions', 'growya.subscriptions.admin', 'growya.subscriptions.*', 'bag'],
            ['Review moderation', 'admin.business-reviews.index', 'admin.business-reviews.*', 'star'],
        ];

        $isAccountSettings = request()->routeIs('profile.*', 'settings.*');
    @endphp

    <a href="#main-content" class="g-skip">Skip to content</a>
    <div class="g-overlay" data-g-overlay hidden></div>

    <aside id="workspace-navigation" class="g-sidebar" data-g-sidebar aria-label="Workspace navigation">
        <a href="{{ route('dashboard') }}" class="g-brand" wire:navigate>
            <span class="g-brand-mark">g</span>growya<span class="g-brand-dot">.</span>
        </a>

        <button type="button" class="g-icon-button g-sidebar-close" data-g-menu-close aria-label="Close navigation">
            <x-growya.icon name="close" />
        </button>

        @if ($hasBusinessAccess)
            <div class="g-sidebar-context">
                <small>CURRENT BUSINESS</small>
                <x-growya.icon name="store" />
                {{ $sidebarBusiness->name }}
            </div>
        @endif

        @if ($sidebarUser->currentTeam)
            <div class="g-sidebar-team">
                <livewire:team-switcher />
            </div>
        @endif


        {{-- @if ($sidebarBusiness && Route::has('growya.nearby.location.edit') && $access->can($sidebarUser, $sidebarBusiness, 'business.update'))
    <a
        class="g-nav-item {{ request()->routeIs('growya.nearby.location.*') ? 'is-current' : '' }}"
        href="{{ route('growya.nearby.location.edit', ['business' => $sidebarBusiness->id]) }}"
        wire:navigate
    >
        <x-growya.icon name="pin" />
        Map Location
    </a>
@endif --}}
        {{-- Workspace Navigation --}}
        <p class="g-sidebar-label">Workspace</p>
        <nav>
            <a class="g-nav-item {{ request()->routeIs('dashboard') ? 'is-current' : '' }}"
                href="{{ route('dashboard') }}" @if (request()->routeIs('dashboard')) aria-current="page" @endif
                wire:navigate>
                <x-growya.icon name="home" />Overview
            </a>

            @if (!$isSuperAdmin)
                <a class="g-nav-item {{ request()->routeIs('businesses.index') ? 'is-current' : '' }}"
                    href="{{ route('businesses.index') }}" @if (request()->routeIs('businesses.index')) aria-current="page" @endif
                    wire:navigate>
                    <x-growya.icon name="store" />My businesses
                </a>
            @endif

            <a class="g-nav-item {{ request()->routeIs('growya.notifications.*') ? 'is-current' : '' }}"
                href="{{ route('growya.notifications.index') }}"
                @if (request()->routeIs('growya.notifications.*')) aria-current="page" @endif wire:navigate>
                <x-growya.icon name="bell" />Notifications
            </a>


            @if (Route::has('growya.reminders.preferences'))
                <a class="g-nav-item {{ request()->routeIs('growya.reminders.preferences') ? 'is-current' : '' }}"
                    href="{{ route('growya.reminders.preferences') }}" wire:navigate>
                    <x-growya.icon name="clock" />
                    Reminder Preferences
                </a>
            @endif

            @if (Route::has('growya.support.index'))
                <a class="g-nav-item {{ request()->routeIs('growya.support.*') ? 'is-current' : '' }}"
                    href="{{ route('growya.support.index') }}"
                    @if (request()->routeIs('growya.support.*')) aria-current="page" @endif wire:navigate>
                    <x-growya.icon name="chat" />Support Tickets
                </a>
            @endif
        </nav>

        {{-- Business Management --}}
        @if ($hasBusinessAccess)
            <p class="g-sidebar-label">Business management</p>
            <nav class="my-3">@foreach(['categories','attributes'] as $kind) @if($access->can($sidebarUser,$sidebarBusiness,$kind.'.view'))<a class="g-nav-item" href="{{ route('businesses.catalog.index',[$sidebarBusiness,$kind]) }}">{{ ucfirst($kind) }}</a>@endif @endforeach</nav>
            @if ($sidebarBusiness->verification_status === 'approved')
                <nav>
                    @foreach ($businessMenu as [$label, $routeName, $icon, $permission])
                        @if (Route::has($routeName) && $access->can($sidebarUser, $sidebarBusiness, $permission))
                            @if (true)
                                @php
                                    $current =
                                        request()->routeIs($routeName) ||
                                        (str_ends_with($routeName, '.index') &&
                                            request()->routeIs(substr($routeName, 0, -5) . '*'));
                                @endphp
                                <a class="g-nav-item {{ $current ? 'is-current' : '' }}"
                                    href="{{ route($routeName, $sidebarBusiness) }}"
                                    @if ($current) aria-current="page" @endif wire:navigate>
                                    <x-growya.icon :name="$icon" />{{ $label }}
                                </a>
                            @endif
                        @endif
                    @endforeach

                    @if (Route::has('businesses.wishlist.index') && $access->can($sidebarUser, $sidebarBusiness, 'wishlist.view'))
                        @php $wishlistCurrent = request()->routeIs('businesses.wishlist.*'); @endphp
                        <a class="g-nav-item {{ $wishlistCurrent ? 'is-current' : '' }}"
                            href="{{ route('businesses.wishlist.index', $sidebarBusiness) }}"
                            @if ($wishlistCurrent) aria-current="page" @endif wire:navigate>
                            <x-growya.icon name="heart" />Wishlist leads
                        </a>
                    @endif

                    @if ($isSuperAdmin && Route::has('growya.plan-overrides.edit'))
                        @php $overrideActive = request()->routeIs('growya.plan-overrides.*'); @endphp
                        <a class="g-nav-item {{ $overrideActive ? 'is-current' : '' }}"
                            href="{{ route('growya.plan-overrides.edit', $sidebarBusiness) }}"
                            @if ($overrideActive) aria-current="page" @endif wire:navigate>
                            <x-growya.icon name="settings" />Custom plan limits
                        </a>
                    @endif

                    @if ($access->can($sidebarUser, $sidebarBusiness, 'staff.view'))
                        @php $staffCurrent = request()->routeIs('businesses.staff.*'); @endphp
                        <a class="g-nav-item {{ $staffCurrent ? 'is-current' : '' }}"
                            href="{{ route('businesses.staff.index', $sidebarBusiness) }}"
                            @if ($staffCurrent) aria-current="page" @endif wire:navigate>
                            <x-growya.icon name="users" />Staff management
                        </a>
                    @endif


                    @if (Route::has('growya.crm.staff.dashboard') && $access->can($sidebarUser, $sidebarBusiness, 'customers.view'))
                        @php $staffCrmCurrent = request()->routeIs('growya.crm.staff.*'); @endphp
                        <a class="g-nav-item {{ $staffCrmCurrent ? 'is-current' : '' }}"
                            href="{{ route('growya.crm.staff.dashboard', $sidebarBusiness) }}"
                            @if ($staffCrmCurrent) aria-current="page" @endif wire:navigate>
                            <x-growya.icon name="users" />Staff CRM
                        </a>
                    @endif

                    @if ($sidebarBusiness && $access->can($sidebarUser, $sidebarBusiness, 'customers.view'))
                        <a class="g-nav-item {{ request()->routeIs('growya.crm.kanban.*') ? 'is-current' : '' }}"
                            href="{{ route('growya.crm.kanban.index', ['business' => $sidebarBusiness->id]) }}"
                            wire:navigate>
                            <x-growya.icon name="grid" />
                            CRM Kanban
                        </a>
                    @endif

                    @if (Route::has('growya.crm.incentives.index') && $access->can($sidebarUser, $sidebarBusiness, 'incentives.view'))
                        @php $incentivesCurrent = request()->routeIs('growya.crm.incentives.*'); @endphp
                        <a class="g-nav-item {{ $incentivesCurrent ? 'is-current' : '' }}"
                            href="{{ route('growya.crm.incentives.index', $sidebarBusiness) }}"
                            @if ($incentivesCurrent) aria-current="page" @endif wire:navigate>
                            <x-growya.icon name="wallet" />CRM Incentives
                        </a>
                    @endif
                    @if (Route::has('growya.finance.index') && $access->can($sidebarUser, $sidebarBusiness, 'finance.view'))
                        @php $financeCurrent = request()->routeIs('growya.finance.*'); @endphp
                        <a class="g-nav-item {{ $financeCurrent ? 'is-current' : '' }}"
                            href="{{ route('growya.finance.index', $sidebarBusiness) }}"
                            @if ($financeCurrent) aria-current="page" @endif wire:navigate>
                            <x-growya.icon name="wallet" />Business Finance
                        </a>
                    @endif

                @foreach(['ai'=>['AI studio','ai.view'],'social'=>['Social publishing','social.view'],'analytics'=>['Daily analytics','analytics.view'],'commerce'=>['Shipping & tax','commerce.view'],'accounting'=>['Accounting & cash flow','accounting.view'],'delivery'=>['Delivery confirmation','delivery.view'],'payments'=>['Gateway payments','payments.view']] as $key=>[$label,$permission])
                    @if($access->can($sidebarUser,$sidebarBusiness,$permission))<a class="g-nav-item" href="{{ route('extensions.workspace',[$sidebarBusiness,$key]) }}"><x-growya.icon name="sparkles" />{{ $label }}</a>@endif
                @endforeach
                </nav>
            @else
                <p class="g-sidebar-note" style="padding: 0 1rem; font-size: .8rem; opacity: .7;">
                    Approval pending. Approve hone ke baad menu yahan dikhega.
                </p>
            @endif
        @endif

        {{-- Administration / Customer Marketplace --}}
        @if ($isSuperAdmin)
            <p class="g-sidebar-label">Administration</p><a class="g-nav-item" href="{{ route('extensions.ai.admin') }}"><x-growya.icon name="sparkles" />AI plans & approvals</a><a class="g-nav-item" href="{{ route('extensions.admin') }}"><x-growya.icon name="settings" />Content & system tools</a>
            <nav>
                @foreach ($adminMenu as [$label, $routeName, $active, $icon])
                    @if (Route::has($routeName))
                        @php $adminCurrent = request()->routeIs($active); @endphp
                        <a class="g-nav-item {{ $adminCurrent ? 'is-current' : '' }}" href="{{ route($routeName) }}"
                            @if ($adminCurrent) aria-current="page" @endif wire:navigate>
                            <x-growya.icon :name="$icon" />{{ $label }}
                        </a>
                    @endif
                @endforeach

                @if (Route::has('growya.admin.wishlist.index'))
                    @php $adminWishlistCurrent = request()->routeIs('growya.admin.wishlist.*'); @endphp
                    <a class="g-nav-item {{ $adminWishlistCurrent ? 'is-current' : '' }}"
                        href="{{ route('growya.admin.wishlist.index') }}"
                        @if ($adminWishlistCurrent) aria-current="page" @endif wire:navigate>
                        <x-growya.icon name="heart" />Wishlist interests
                    </a>
                @endif
            </nav>
        @else
            <p class="g-sidebar-label">Your marketplace</p>
            <nav>

                @if (Route::has('growya.referrals.mine'))
                    <a class="g-nav-item {{ request()->routeIs('growya.referrals.mine') ? 'is-current' : '' }}"
                        href="{{ route('growya.referrals.mine') }}" wire:navigate>
                        <x-growya.icon name="users" />
                        My Referrals
                    </a>
                @endif
                @if (Route::has('growya.chat.mine'))
                    @php $chatCurrent = request()->routeIs('growya.chat.mine', 'growya.chat.show'); @endphp
                    <a class="g-nav-item {{ $chatCurrent ? 'is-current' : '' }}"
                        href="{{ route('growya.chat.mine') }}"
                        @if ($chatCurrent) aria-current="page" @endif wire:navigate>
                        <x-growya.icon name="chat" />My Messages
                    </a>
                @endif

                @if (Route::has('growya.claims.mine'))
                    @php $claimsCurrent = request()->routeIs('growya.claims.mine', 'growya.claims.create'); @endphp
                    <a class="g-nav-item {{ $claimsCurrent ? 'is-current' : '' }}"
                        href="{{ route('growya.claims.mine') }}"
                        @if ($claimsCurrent) aria-current="page" @endif wire:navigate>
                        <x-growya.icon name="store" />My business claims
                    </a>
                @endif

                <a class="g-nav-item {{ request()->routeIs('growya.orders.*') ? 'is-current' : '' }}"
                    href="{{ route('growya.orders.mine') }}"
                    @if (request()->routeIs('growya.orders.*')) aria-current="page" @endif wire:navigate>
                    <x-growya.icon name="bag" />My orders
                </a>

                @if (Route::has('growya.addresses.index'))
                    <a class="g-nav-item {{ request()->routeIs('growya.addresses.*') ? 'is-current' : '' }}"
                        href="{{ route('growya.addresses.index') }}"
                        @if (request()->routeIs('growya.addresses.*')) aria-current="page" @endif wire:navigate>
                        <x-growya.icon name="map-pin" />Manage addresses
                    </a>
                @endif

                @if (Route::has('growya.wishlist.mine'))
                    <a class="g-nav-item {{ request()->routeIs('growya.wishlist.*') ? 'is-current' : '' }}"
                        href="{{ route('growya.wishlist.mine') }}"
                        @if (request()->routeIs('growya.wishlist.*')) aria-current="page" @endif wire:navigate>
                        <x-growya.icon name="heart" />My wishlist
                    </a>
                @endif

                <a class="g-nav-item {{ request()->routeIs('public.business.following') ? 'is-current' : '' }}"
                    href="{{ route('public.business.following') }}"
                    @if (request()->routeIs('public.business.following')) aria-current="page" @endif wire:navigate>
                    <x-growya.icon name="heart" />Following
                </a>

                <a class="g-nav-item {{ request()->routeIs('growya.business-request.*') ? 'is-current' : '' }}"
                    href="{{ route('growya.business-request.status') }}"
                    @if (request()->routeIs('growya.business-request.*')) aria-current="page" @endif wire:navigate>
                    <x-growya.icon name="clock" />Business applications
                </a>

                <a class="g-nav-item {{ request()->routeIs('businesses.create') ? 'is-current' : '' }}"
                    href="{{ route('businesses.create') }}"
                    @if (request()->routeIs('businesses.create')) aria-current="page" @endif wire:navigate>
                    <x-growya.icon name="plus" />Register a business
                </a>
            </nav>
        @endif

        {{-- Sidebar Footer --}}
        <div class="g-sidebar-foot">
            <nav>
                <a class="g-nav-item" href="{{ route('public.marketplace.index') }}">
                    <x-growya.icon name="globe" />Explore marketplace
                    <x-growya.icon name="arrow-up-right" class="ml-auto" />
                </a>
                <a class="g-nav-item {{ $isAccountSettings ? 'is-current' : '' }}"
                    href="{{ route('profile.edit') }}" @if ($isAccountSettings) aria-current="page" @endif
                    wire:navigate>
                    <x-growya.icon name="settings" />Account settings
                </a>
            </nav>

            <div class="g-sidebar-user">
                <span class="g-avatar">{{ $sidebarUser->initials() }}</span>
                <div>
                    <strong>{{ $sidebarUser->name }}</strong>
                    <small>{{ $sidebarRole }}</small>
                </div>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" aria-label="Log out" title="Log out">
                        <x-growya.icon name="logout" />
                    </button>
                </form>
            </div>
        </div>
    </aside>

    {{-- Main Workspace Content --}}
    <div class="g-workspace">
        <header class="g-topbar">
            <div class="g-topbar-start">
                <button type="button" class="g-icon-button g-menu-toggle" data-g-menu-toggle aria-expanded="false"
                    aria-controls="workspace-navigation" aria-label="Open navigation">
                    <x-growya.icon name="menu" />
                </button>
                <div class="g-breadcrumb">
                    <x-growya.icon name="grid" />
                    <span>{{ $sidebarRole }}</span>
                    <span>/</span>
                    <strong>{{ $title ?? 'Workspace' }}</strong>
                </div>
            </div>

            <div class="g-topbar-actions">
                <a class="g-topbar-search" href="{{ route('public.search.index') }}">
                    <x-growya.icon name="search" />Search marketplace
                </a>
                <span class="g-date">{{ now()->format('D, d M Y') }}</span>
                <button type="button" class="g-icon-button" x-data
                    @click="$flux.appearance = $flux.dark ? 'light' : 'dark'" aria-label="Switch light or dark theme">
                    <x-growya.icon name="moon" />
                </button>
                <x-growya-live-notification-bell />
                <a href="{{ route('profile.edit') }}" class="g-avatar" aria-label="Account settings"
                    title="{{ $sidebarUser->name }}" wire:navigate>
                    {{ $sidebarUser->initials() }}
                </a>
            </div>
        </header>

        <main id="main-content">
            {{ $slot }}
        </main>

        <footer class="g-workspace-footer">
            <span>© {{ date('Y') }} Growya. Built for local growth.</span>
            <a href="{{ route('home') }}">Visit website ↗</a>
        </footer>
    </div>

    @persist('toast')
        <flux:toast.group>
            <flux:toast />
        </flux:toast.group>
    @endpersist
    @fluxScripts
<x-growya-web-push />
</body>

</html>
