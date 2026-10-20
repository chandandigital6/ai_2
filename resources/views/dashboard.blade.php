<x-layouts::app :title="__('Dashboard')">

{{-- <x-growya-notification-bell /> --}}

<x-growya-live-notification-bell />
   
   @php
    $showroomLive = $business && $business->status === 'active' && $business->verification_status === 'approved';

    $statCards = [
        [
            'label' => 'Showroom Visits',
            'value' => $stats['views'],
            'icon' => 'building-storefront',
            'hint' => 'Recorded business views',
        ],
        [
            'label' => 'Followers',
            'value' => $stats['followers'],
            'icon' => 'users',
            'hint' => 'Recorded follower count',
        ],
    ];

    if ($canProducts) {
        $statCards[] = [
            'label' => 'Products',
            'value' => $stats['products'],
            'icon' => 'cube',
            'hint' => 'All your products',
        ];
    }

    if ($canServices) {
        $statCards[] = [
            'label' => 'Services',
            'value' => $stats['services'],
            'icon' => 'briefcase',
            'hint' => 'All your services',
        ];
        $statCards[] = [
            'label' => 'Service Enquiries',
            'value' => $stats['enquiries'],
            'icon' => 'inbox',
            'hint' => 'All enquiries',
        ];
        $statCards[] = [
            'label' => 'Open Appointments',
            'value' => $stats['appointments'],
            'icon' => 'calendar-days',
            'hint' => 'Pending & confirmed',
        ];
    }
@endphp
    <div class="mx-auto w-full max-w-7xl space-y-7 p-4 md:p-6">

        <section class="overflow-hidden rounded-3xl bg-slate-950 p-6 text-white md:p-8">
            <div class="flex flex-wrap items-start justify-between gap-4">
                <div>
                    <p class="text-sm text-emerald-300">{{ now()->format('l, d F Y') }}</p>
                    <h1 class="mt-2 text-2xl font-black md:text-3xl">Welcome, {{ auth()->user()->name }}</h1>
                    <p class="mt-2 text-sm text-slate-300">Manage your Growya business from one place.</p>
                </div>
                <a href="{{ route('public.marketplace.index') }}" target="_blank" rel="noopener noreferrer"
                    class="rounded-xl bg-emerald-500 px-4 py-2.5 text-sm font-bold text-slate-950">Explore Marketplace
                    ↗</a>
            </div>
        </section>

        @role('super_admin')
        <section class="grid gap-4 md:grid-cols-2">
          <a href="{{ route('growya.roles.matrix') }}" class="rounded-2xl border border-emerald-200 bg-emerald-50 p-5 transition hover:shadow-lg dark:border-emerald-900 dark:bg-emerald-950">
            <p class="text-xs font-bold uppercase tracking-wider text-emerald-700 dark:text-emerald-300">Administration</p>
            <h2 class="mt-2 text-xl font-extrabold text-zinc-900 dark:text-white">Dynamic Roles & Permissions →</h2>
            <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-300">Control who can access global Growya modules.</p>
          </a>
          @if(Route::has('admin.business-verifications.index'))
          <a href="{{ route('admin.business-verifications.index') }}" class="rounded-2xl border border-zinc-200 bg-white p-5 transition hover:shadow-lg dark:border-zinc-700 dark:bg-zinc-900">
            <p class="text-xs font-bold uppercase tracking-wider text-zinc-500">Verification</p>
            <h2 class="mt-2 text-xl font-extrabold">Business Approvals →</h2>
            <p class="mt-1 text-sm text-zinc-500">Review new business applications.</p>
          </a>
          @endif
        </section>
        @endrole

        @if ($businesses->isEmpty())
            <section class="rounded-2xl border border-zinc-200 p-8 dark:border-zinc-700">
                <h2 class="text-xl font-bold">No business assigned yet</h2>
                <p class="mt-2 text-sm text-zinc-500">Create a business or ask its owner to grant you access.</p>
                <a class="mt-4 inline-block rounded-xl bg-emerald-600 px-5 py-2.5 font-semibold text-white"
                    href="{{ route('businesses.index') }}">Go to Businesses</a>
            </section>
        @else
            <section
                class="flex flex-wrap items-end justify-between gap-4 rounded-2xl border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-900">
                <div>
                    <p class="text-xs font-bold uppercase tracking-wide text-zinc-500">Current Business</p>
                    <h2 class="mt-1 text-xl font-black">{{ $business->name }}</h2>
                    <div class="mt-2 flex flex-wrap gap-2 text-xs font-semibold">
                        <span
                            class="rounded-full bg-zinc-100 px-3 py-1 text-zinc-700 dark:bg-zinc-800 dark:text-zinc-200">{{ ucfirst($business->status) }}</span>
                        <span
                            class="rounded-full px-3 py-1 {{ $business->verification_status === 'approved' ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700' }}">{{ ucfirst($business->verification_status) }}</span>
                    </div>
                </div>
                <form method="GET" action="{{ route('dashboard') }}" class="flex flex-wrap items-end gap-2">
                    <label class="block text-xs font-bold text-zinc-500" for="dashboard-business">Switch
                        business</label>
                    <select id="dashboard-business" name="business_id"
                        class="min-w-48 rounded-xl border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 dark:border-zinc-600 dark:bg-zinc-800 dark:text-white">
                        @foreach ($businesses as $option)
                            <option value="{{ $option->id }}" @selected($option->id === $business->id)>{{ $option->name }}
                            </option>
                        @endforeach
                    </select>
                    <button type="submit"
                        class="rounded-xl bg-emerald-600 px-4 py-2 text-sm font-bold text-white">Open</button>
                </form>
            </section>

            <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
                @foreach ($statCards as $card)
                    <div
                        class="rounded-2xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
                        <div class="flex items-center justify-between gap-2"><span
                                class="text-sm font-bold text-zinc-500">{{ $card['label'] }}</span><span
                                class="rounded-xl bg-emerald-50 p-2 text-emerald-700">
                                <flux:icon :name="$card['icon']" class="size-5" />
                            </span></div>
                        <p class="mt-4 text-3xl font-black tabular-nums">{{ number_format((int) $card['value']) }}</p>
                        <p class="mt-1 text-xs text-zinc-500">{{ $card['hint'] }}</p>
                    </div>
                @endforeach
            </section>

            <section class="grid gap-5 lg:grid-cols-2">
                @if ($canServices)
                    <div class="rounded-2xl border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-900">
                        <div class="flex items-center justify-between gap-3">
                            <h3 class="text-lg font-black">Recent Enquiries</h3><a
                                href="{{ route('businesses.service-enquiries.index', $business) }}"
                                class="text-sm font-semibold text-emerald-600">View all →</a>
                        </div>
                        <div class="mt-4 divide-y divide-zinc-200 dark:divide-zinc-700">
                            @forelse($recentEnquiries as $enquiry)
                                <a href="{{ route('businesses.service-enquiries.show', [$business, $enquiry]) }}"
                                    class="flex items-center justify-between gap-3 py-3 hover:text-emerald-600">
                                    <div>
                                        <p class="font-semibold">{{ $enquiry->customer_name ?: 'Customer' }}</p>
                                        <p class="text-xs text-zinc-500">{{ $enquiry->service?->name ?: 'Service' }} ·
                                            {{ $enquiry->created_at->format('d M Y') }}</p>
                                    </div><span
                                        class="text-xs font-semibold">{{ ucfirst(str_replace('_', ' ', $enquiry->status)) }}</span>
                                </a>
                            @empty <p class="py-5 text-sm text-zinc-500">No enquiries yet.</p>
                            @endforelse
                        </div>
                    </div>
                    <div class="rounded-2xl border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-900">
                        <div class="flex items-center justify-between gap-3">
                            <h3 class="text-lg font-black">Upcoming Appointments</h3><a
                                href="{{ route('businesses.appointments.index', $business) }}"
                                class="text-sm font-semibold text-emerald-600">View all →</a>
                        </div>
                        <div class="mt-4 divide-y divide-zinc-200 dark:divide-zinc-700">
                            @forelse($upcomingAppointments as $item)
                                <a href="{{ route('businesses.appointments.show', [$business, $item]) }}"
                                    class="flex items-center justify-between gap-3 py-3 hover:text-emerald-600">
                                    <div>
                                        <p class="font-semibold">{{ $item->customer_name ?: 'Customer' }}</p>
                                        <p class="text-xs text-zinc-500">{{ $item->service?->name ?: 'Service' }}</p>
                                    </div>
                                    <p class="text-right text-xs font-semibold">
                                        {{ $item->appointment_date?->format('d M Y') }}<span
                                            class="block text-zinc-500">{{ substr($item->appointment_time, 0, 5) }}</span>
                                    </p>
                                </a>
                            @empty <p class="py-5 text-sm text-zinc-500">No upcoming appointments.</p>
                            @endforelse
                        </div>
                    </div>
                @endif
            </section>

            <section class="rounded-2xl border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-900">
                <h3 class="text-lg font-black">Quick Actions</h3>
                <div class="mt-4 grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                    @if ($canProducts)
                        <a href="{{ route('businesses.products.index', $business) }}"
                            class="rounded-xl border border-zinc-200 p-4 font-semibold hover:border-emerald-500 dark:border-zinc-700">Products
                            ↗</a>
                    @endif
                    @if ($canServices)
                        <a href="{{ route('businesses.services.index', $business) }}"
                            class="rounded-xl border border-zinc-200 p-4 font-semibold hover:border-emerald-500 dark:border-zinc-700">Services
                            ↗</a>
                        <a href="{{ route('businesses.service-follow-ups.index', $business) }}"
                            class="rounded-xl border border-zinc-200 p-4 font-semibold hover:border-emerald-500 dark:border-zinc-700">Follow-ups
                            ↗</a>
                        <a href="{{ route('businesses.gallery.index', $business) }}"
                            class="rounded-xl border border-zinc-200 p-4 font-semibold hover:border-emerald-500 dark:border-zinc-700">Gallery
                            ↗</a>
                    @endif
                    @if ($canEditProducts)
                        <a href="{{ route('businesses.products.create', $business) }}"
                            class="rounded-xl bg-emerald-600 p-4 font-semibold text-white">+ Add Product</a>
                    @endif
                    @if ($canEditServices)
                        <a href="{{ route('businesses.services.create', $business) }}"
                            class="rounded-xl bg-emerald-600 p-4 font-semibold text-white">+ Add Service</a>
                    @endif
                    @if ($canUpdateServices)
                        <a href="{{ route('businesses.appointments.create', $business) }}"
                            class="rounded-xl border border-zinc-200 p-4 font-semibold hover:border-emerald-500 dark:border-zinc-700">+
                            Appointment</a>
                    @endif
                    @if ($showroomLive)
                        <a href="{{ route('public.business.home', $business->slug) }}" target="_blank"
                            rel="noopener noreferrer"
                            class="rounded-xl border border-emerald-400 p-4 font-semibold text-emerald-600">Public
                            Showroom ↗</a>
                    @else
                        <div class="rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800">Public
                            Showroom requires Active + Approved status.</div>
                    @endif
                    @if ($superAdmin)
                        <a href="{{ route('admin.business-verifications.index') }}"
                            class="rounded-xl border border-zinc-200 p-4 font-semibold hover:border-emerald-500 dark:border-zinc-700">Business
                            Approvals ↗</a>
                    @endif
                </div>
            </section>
        @endif
    </div>
</x-layouts::app>
