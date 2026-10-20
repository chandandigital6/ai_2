<x-layouts::app :title="'Plan limits: ' . $plan->name">
    <div class="g-page">
        <div class="g-page-heading" style="display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:1.5rem; flex-wrap:wrap; gap:1rem;">
            <div>
                <p class="g-kicker" style="font-size:0.8rem; text-transform:uppercase; letter-spacing:0.05em; color:#64748b; margin:0;">Subscriptions / Manage Plans</p>
                <h1 style="font-size:1.75rem; font-weight:700; margin:0.25rem 0;">{{ $plan->name }} — Feature limits</h1>
                <p style="color:#64748b; margin:0;">Configure allowances. Enter <code>-1</code> for unlimited, <code>0</code> to disable.</p>
            </div>
            <a class="g-btn g-btn-secondary" href="{{ route('growya.plans.index') }}">← Back to plans</a>
        </div>

        @if (session('success'))
            <div class="g-alert g-tone-green" style="padding:12px 16px; border-radius:8px; margin-bottom:1.25rem; background:#dcfce7; color:#166534;">
                {{ session('success') }}
            </div>
        @endif

        <div class="g-panel" style="padding:1.5rem; border-radius:12px; background:var(--g-panel-bg, #fff); border:1px solid var(--g-border, #e2e8f0); max-width:850px;">
            <form method="POST" action="{{ route('growya.entitlements.update', $plan) }}">
                @csrf
                @method('PATCH')

                <div style="display:flex; flex-direction:column; gap:1.25rem;">
                    @foreach (\App\Services\GrowyaPlanLimitService::KEYS as $key => $label)
                        <div style="padding-bottom:1.25rem; border-bottom:1px solid #f1f5f9; display:grid; grid-template-columns:repeat(auto-fit, minmax(240px, 1fr)); gap:1rem; align-items:center;">
                            <div>
                                <label for="{{ $key }}" style="display:block; font-weight:600; font-size:0.95rem; color:#0f172a;">{{ $label }}</label>
                                <span style="font-size:0.8rem; color:#64748b;">Key: <code>{{ $key }}</code></span>
                            </div>

                            <div>
                                @if ($key === 'featured_listing')
                                    <select id="{{ $key }}" name="{{ $key }}" style="width:100%; border:1px solid #cbd5e1; border-radius:8px; padding:8px 10px; background:#fff;">
                                        <option value="0" @selected(old($key, $existing[$key] ?? '0') === '0')>Disabled</option>
                                        <option value="1" @selected(old($key, $existing[$key] ?? '0') === '1')>Enabled</option>
                                    </select>
                                @else
                                    <div style="display:flex; align-items:center; gap:0.5rem;">
                                        <input id="{{ $key }}" type="number" min="-1" max="1000000" required
                                            name="{{ $key }}"
                                            value="{{ old($key, $existing[$key] ?? \App\Services\GrowyaPlanLimitService::FALLBACK[$key]) }}"
                                            style="width:100%; border:1px solid #cbd5e1; border-radius:8px; padding:8px 10px; background:#fff;">
                                        <span style="font-size:0.75rem; color:#64748b; white-space:nowrap;">(-1 = ∞)</span>
                                    </div>
                                @endif

                                @error($key)
                                    <p style="color:#ef4444; font-size:0.8rem; margin-top:4px;">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                    @endforeach
                </div>

                <div style="margin-top:1.5rem; display:flex; justify-content:flex-end;">
                    <button class="g-btn g-btn-primary" type="submit" style="padding:10px 24px;">
                        Save feature limits
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-layouts::app>