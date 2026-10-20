<x-layouts::app :title="'Plan usage — ' . $business->name">
    <div class="mx-auto max-w-4xl p-6 space-y-5">
        <h1 class="text-2xl font-bold">{{ $business->name }} — plan usage</h1>
        <p>Active subscription: <strong>{{ $active?->plan?->name ?? 'None — free fallback' }}</strong></p>
        <p>Expiry: {{ $active?->ends_at?->format('d M Y') ?? '—' }}</p>
        <div class="grid gap-4 md:grid-cols-2">
            @foreach ($usage as $key => $item)
                <div class="rounded-xl border p-4">
                    <h2 class="font-semibold">{{ $item['label'] }}</h2>
                    @if ($key !== 'featured_listing' && $item['allowed'] !== null && $item['used'] >= $item['allowed'])
                        <p class="mt-2 font-semibold text-red-600">Limit reached — upgrade or renew to add more.</p>
                    @elseif(
                        $key !== 'featured_listing' &&
                            $item['allowed'] !== null &&
                            $item['allowed'] > 0 &&
                            $item['used'] / $item['allowed'] >= 0.8)
                        <p class="mt-2 font-semibold text-amber-600">Approaching limit —
                            {{ $item['allowed'] - $item['used'] }} remaining.</p>
                    @endif
                    <p class="mt-2 text-lg">
                        @if ($key === 'featured_listing')
                            {{ $item['allowed'] ? 'Enabled' : 'Disabled' }}
                        @else
                            {{ $item['used'] }} / {{ $item['allowed'] === null ? 'Unlimited' : $item['allowed'] }}
                        @endif
                    </p>
                </div>
            @endforeach
        </div>
        <a class="underline" href="{{ route('growya.subscriptions.business', $business) }}">Manage subscription</a>
        @if (auth()->user()?->hasRole('super_admin', 'web'))
            <a class="g-btn g-btn-secondary" href="{{ route('growya.plan-overrides.edit', $business) }}">
                ⚙️ Manage Overrides
            </a>
        @endif
    </div>
</x-layouts::app>
