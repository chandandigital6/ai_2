<x-layouts::app :title="__('Business management')">
    <div class="g-page">
        <div class="g-page-heading">
            <div>
                <p class="g-kicker">Your digital showrooms</p>
                <h1>{{ auth()->user()->hasRole('super_admin', 'web') ? 'Business management' : 'My businesses' }}</h1>
                <p>Manage business details, catalogues and customer connections.</p>
            </div>
            @can('create', \App\Models\Business::class)
                <a class="g-btn g-btn-primary" href="{{ route('businesses.create') }}"><x-growya.icon name="plus" />Register
                    a business</a>
            @endcan


        </div>
        @if (session('success'))
            <div class="g-alert g-tone-green" role="status">{{ session('success') }}</div>
        @endif
        <section class="g-panel">
            <div class="g-panel-head">
                <div>
                    <h2>Your businesses</h2>
                    <p>{{ number_format($businesses->total()) }}
                        {{ $businesses->total() === 1 ? 'business' : 'businesses' }} found</p>
                </div>
                <form method="GET" action="{{ route('businesses.index') }}" class="g-actions"><label
                        for="business-search" class="sr-only">Search businesses</label><input id="business-search"
                        type="search" name="search" value="{{ request('search') }}" placeholder="Name, slug or phone"
                        class="rounded-xl border bg-transparent px-4 py-2 text-xs"><button class="g-btn g-btn-secondary"
                        type="submit"><x-growya.icon name="search" />Search</button>
                    @if (request('search'))
                        <a class="g-link" href="{{ route('businesses.index') }}">Clear</a>
                    @endif
                </form>
            </div>
            <div class="g-table-wrap">
                <table class="g-table" style="min-width:690px">
                    <thead>
                        <tr>
                            <th>Business</th>
                            <th>Owner</th>
                            <th>Category / city</th>
                            <th>Verification</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($businesses as $business)
                            @php
                                $access = app(\App\Services\BusinessAccessService::class);
                                $canOverview = $access->can(auth()->user(), $business, 'business.view');
                            @endphp
                            <tr>
                                <td>
                                    <div class="g-row-main"><span
                                            class="g-avatar">{{ mb_strtoupper(mb_substr($business->name, 0, 2)) }}</span>
                                        <div>
                                            <p class="g-row-title">{{ $business->name }}</p>
                                            <p class="g-row-subtitle">{{ ucfirst($business->status) }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td>{{ $business->owner?->name ?? '—' }}</td>
                                <td>{{ $business->category?->name ?? 'Uncategorised' }}<p class="g-row-subtitle">
                                        {{ $business->city?->name ?? 'City not set' }}</p>
                                </td>
                                <td><x-growya.status :value="$business->verification_status" /></td>
                                <td>
                                    <div class="g-actions">
                                        <a href="{{ route('growya.plan-overrides.edit', $business) }}"
                                            class="g-btn g-btn-secondary" title="Plan Overrides">
                                            Limits ⚙️
                                        </a>

                                        @if ($canOverview)
                                            <a class="g-btn g-btn-secondary g-btn-small"
                                                href="{{ route('businesses.show', $business) }}">Overview<x-growya.icon
                                                    name="arrow-up-right" /></a>
                                        @elseif($business->verification_status === 'approved' && $business->status === 'active')
                                            <a class="g-btn g-btn-secondary g-btn-small"
                                                href="{{ route('dashboard', ['business_id' => $business->id]) }}">Workspace<x-growya.icon
                                                    name="arrow-up-right" /></a>
                                        @endif @can('update', $business)
                                        <a class="g-link" href="{{ route('businesses.edit', $business) }}">Edit</a>
                                        @endcan @if (auth()->user()->hasRole('super_admin', 'web'))
                                            <a class="g-link"
                                                href="{{ route('admin.business-verifications.show', $business) }}">Review</a>
                                        @endif @can('delete', $business)
                                        <form method="POST" action="{{ route('businesses.destroy', $business) }}"
                                            onsubmit="return confirm('Move this business to trash?')">@csrf
                                            @method('DELETE')<button type="submit" class="g-link"
                                                style="color:#b45766">Delete</button></form>
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @empty<tr>
                            <td colspan="5"><x-growya.empty title="Your business journey starts here"
                                    description="Register a business to create your digital showroom."
                                    icon="store" :href="route('businesses.create')" action="Register a business" /></td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
    {{ $businesses->links() }}
</div>
</x-layouts::app>
