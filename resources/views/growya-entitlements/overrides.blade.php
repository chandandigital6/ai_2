<x-layouts::app :title="'Plan overrides — ' . $business->name">
    <div class="g-page">
        {{-- Header --}}
        <div class="g-page-heading"
            style="display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:1.5rem; flex-wrap:wrap; gap:1rem;">
            <div>
                <p class="g-kicker"
                    style="font-size:0.8rem; text-transform:uppercase; letter-spacing:0.05em; color:#64748b; margin:0;">
                    Administration / Business Limits</p>
                <h1 style="font-size:1.75rem; font-weight:700; margin:0.25rem 0;">Business plan overrides</h1>
                <p style="color:#64748b; margin:0;">Assign custom quotas or toggle features for
                    <strong>{{ $business->name }}</strong>.</p>
            </div>
            <div style="display:flex; gap:0.5rem;">
                <a class="g-btn g-btn-secondary" href="{{ route('growya.entitlements.usage', $business) }}"
                    target="_blank">
                    View as Owner ↗
                </a>
                <a class="g-btn g-btn-secondary" href="{{ route('businesses.index') }}">
                    ← All businesses
                </a>
            </div>
        </div>

        @if (session('success'))
            <div class="g-alert g-tone-green"
                style="padding:12px 16px; border-radius:8px; margin-bottom:1.25rem; background:#dcfce7; color:#166534;">
                {{ session('success') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="g-alert"
                style="padding:12px 16px; border-radius:8px; margin-bottom:1.25rem; background:#fee2e2; color:#991b1b;">
                {{ $errors->first() }}
            </div>
        @endif

        {{-- Set / Update Override Form --}}
        <section class="g-panel"
            style="padding:1.5rem; border-radius:12px; background:var(--g-panel-bg, #fff); border:1px solid var(--g-border, #e2e8f0); margin-bottom:1.5rem;">
            <h2 style="font-size:1.15rem; font-weight:700; margin:0 0 1rem; color:#0f172a;">Set or update feature
                override</h2>
            <form method="POST" action="{{ route('growya.plan-overrides.save', $business) }}"
                style="display:grid; gap:1rem; grid-template-columns:repeat(auto-fit, minmax(220px, 1fr)); align-items:flex-end;">
                @csrf
                <div>
                    <label style="display:block; font-size:0.8rem; font-weight:600; margin-bottom:4px;">Feature
                        key</label>
                    <select name="feature_key" class="w-full" required
                        style="width:100%; border:1px solid #cbd5e1; border-radius:8px; padding:8px; background:#fff;">
                        @foreach (\App\Services\GrowyaPlanLimitService::KEYS as $key => $label)
                            <option value="{{ $key }}">{{ $label }} ({{ $key }})</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label style="display:block; font-size:0.8rem; font-weight:600; margin-bottom:4px;">Override
                        value</label>
                    <input type="number" min="-1" max="1000000" name="value" required
                        placeholder="-1 = ∞, featured: 0/1"
                        style="width:100%; border:1px solid #cbd5e1; border-radius:8px; padding:8px; background:#fff;">
                </div>

                <div>
                    <label style="display:block; font-size:0.8rem; font-weight:600; margin-bottom:4px;">Expires at
                        (Optional)</label>
                    <input type="datetime-local" name="expires_at"
                        style="width:100%; border:1px solid #cbd5e1; border-radius:8px; padding:8px; background:#fff;">
                </div>

                <div style="grid-column: 1 / -1;">
                    <label style="display:block; font-size:0.8rem; font-weight:600; margin-bottom:4px;">Reason for
                        change (Mandatory audit log)</label>
                    <input name="reason" minlength="5" maxlength="1000" required
                        placeholder="e.g. VIP onboarding perk or temporary promotion bonus"
                        style="width:100%; border:1px solid #cbd5e1; border-radius:8px; padding:8px; background:#fff;">
                </div>

                <div style="margin-top:0.5rem;">
                    <button type="submit" class="g-btn g-btn-primary" style="padding:9px 20px;">
                        Save override
                    </button>
                </div>
            </form>
        </section>

        {{-- Current Quotas & Overrides Status --}}
        <h2 style="font-size:1.2rem; font-weight:700; margin:1.5rem 0 1rem; color:#0f172a;">Active quotas & overrides
        </h2>
        <div
            style="display:grid; grid-template-columns:repeat(auto-fit, minmax(280px, 1fr)); gap:1.25rem; margin-bottom:2rem;">
            @foreach ($usage as $key => $item)
                @php
                    $override = $overrides[$key] ?? null;
                    $isFeatured = $key === 'featured_listing';
                    $isUnlimited = $item['allowed'] === null;
                @endphp
                <div class="g-panel"
                    style="padding:1.25rem; border-radius:12px; background:var(--g-panel-bg, #fff); border:1px solid {{ $override ? '#cbd5e1' : '#e2e8f0' }}; position:relative;">
                    @if ($override)
                        <span
                            style="position:absolute; top:12px; right:12px; font-size:0.7rem; font-weight:700; background:#eff6ff; color:#1d4ed8; border:1px solid #bfdbfe; padding:2px 8px; border-radius:999px;">
                            Overridden
                        </span>
                    @endif

                    <h3 style="font-size:0.95rem; font-weight:600; margin:0 0 0.5rem; color:#334155;">
                        {{ $item['label'] }}</h3>

                    <div style="font-size:0.875rem; margin-bottom:0.75rem;">
                        <span style="color:#64748b;">Current Usage:</span>
                        <strong style="color:#0f172a;">{{ $item['used'] }}</strong> /
                        <span style="color:#64748b;">Allowed:</span>
                        <strong style="color:#0f172a;">
                            @if ($isFeatured)
                                {{ $item['allowed'] ? 'Enabled' : 'Disabled' }}
                            @else
                                {{ $isUnlimited ? 'Unlimited' : $item['allowed'] }}
                            @endif
                        </strong>
                    </div>

                    @if ($override)
                        <div
                            style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:8px; padding:10px; font-size:0.8rem; margin-top:0.75rem;">
                            <div><span style="color:#64748b;">Override value:</span>
                                <strong>{{ $override->value }}</strong></div>
                            <div><span style="color:#64748b;">Expires:</span>
                                {{ $override->expires_at ? \Carbon\Carbon::parse($override->expires_at)->format('d M Y, h:i A') : 'Permanent' }}
                            </div>
                            <div style="margin-top:4px; font-style:italic; color:#475569;">"{{ $override->reason }}"
                            </div>

                            <form method="POST"
                                action="{{ route('growya.plan-overrides.remove', [$business, $key]) }}"
                                style="margin-top:8px;" onsubmit="return confirm('Remove this override?');">
                                @csrf
                                @method('DELETE')
                                <div style="display:flex; gap:0.5rem;">
                                    <input required minlength="5" name="reason" placeholder="Reason for removal"
                                        style="flex:1; border:1px solid #cbd5e1; border-radius:6px; padding:4px 8px; font-size:0.75rem;">
                                    <button class="g-btn g-btn-secondary"
                                        style="padding:4px 10px; font-size:0.75rem; color:#dc2626; border-color:#fca5a5;">
                                        Remove
                                    </button>
                                </div>
                            </form>
                        </div>
                    @endif
                </div>
            @endforeach
        </div>

        {{-- Audit Log Table --}}
        <section class="g-panel"
            style="padding:1.5rem; border-radius:12px; background:var(--g-panel-bg, #fff); border:1px solid var(--g-border, #e2e8f0);">
            <h2 style="font-size:1.15rem; font-weight:700; margin:0 0 1rem; color:#0f172a;">Recent override audit trail
            </h2>
            <div class="g-table-wrap" style="overflow-x:auto;">
                <table class="g-table"
                    style="width:100%; border-collapse:collapse; text-align:left; font-size:0.875rem;">
                    <thead>
                        <tr style="border-bottom:2px solid #e2e8f0; color:#64748b; font-size:0.8rem;">
                            <th style="padding:8px 12px;">Feature</th>
                            <th style="padding:8px 12px;">Old value</th>
                            <th style="padding:8px 12px;">New value</th>
                            <th style="padding:8px 12px;">Reason</th>
                            <th style="padding:8px 12px;">Actor ID</th>
                            <th style="padding:8px 12px;">Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($audits as $audit)
                            <tr style="border-bottom:1px solid #f1f5f9;">
                                <td style="padding:10px 12px;"><code>{{ $audit->feature_key }}</code></td>
                                <td style="padding:10px 12px; color:#64748b;">{{ $audit->old_value ?? '—' }}</td>
                                <td style="padding:10px 12px;">
                                    @if ($audit->new_value === null)
                                        <span style="color:#dc2626; font-weight:600;">Removed</span>
                                    @else
                                        <strong>{{ $audit->new_value }}</strong>
                                    @endif
                                </td>
                                <td style="padding:10px 12px;">{{ $audit->reason }}</td>
                                <td style="padding:10px 12px; color:#64748b;">#{{ $audit->actor_id }}</td>
                                <td style="padding:10px 12px; color:#64748b;">
                                    {{ \Carbon\Carbon::parse($audit->created_at)->format('d M Y, h:i A') }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" style="padding:20px; text-align:center; color:#64748b;">No override
                                    history recorded yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </div>
</x-layouts::app>
