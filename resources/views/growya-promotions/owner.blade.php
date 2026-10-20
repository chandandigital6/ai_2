<x-layouts::app title="Paid Promotions" :business="$business">
    <div class="g-page">
        {{-- Page Heading --}}
        <div class="g-page-heading" style="margin-bottom:1.5rem;">
            <div>
                <p class="g-kicker"
                    style="font-size:0.8rem; text-transform:uppercase; letter-spacing:0.05em; color:#64748b; margin:0;">
                    {{ $business->name }}</p>
                <h1 style="font-size:1.75rem; font-weight:700; margin:0.25rem 0;">Paid promotions</h1>
                <p style="color:#64748b; margin:0;">Boost visibility for your business or specific products. Submit your
                    payment reference after completing transfer; activation is verified manually by admin.</p>
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

        {{-- Request Promotion Card --}}
        <section class="g-panel"
            style="padding:1.5rem; border-radius:12px; background:var(--g-panel-bg, #fff); border:1px solid var(--g-border, #e2e8f0); margin-bottom:2rem;">
            <h2 style="font-size:1.15rem; font-weight:700; margin:0 0 1rem; color:#0f172a;">Request a new promotion</h2>

            <form method="POST" action="{{ route('growya.promotions.owner.request', $business) }}"
                x-data="{ targetType: '' }"
                style="display:grid; gap:1.25rem; grid-template-columns:repeat(auto-fit, minmax(240px, 1fr)); align-items:flex-end;">
                @csrf

                <div>
                    <label style="display:block; font-size:0.8rem; font-weight:600; margin-bottom:4px;">Select
                        package</label>
                    <select name="package_id" required
                        @change="targetType = $event.target.selectedOptions[0].dataset.target || ''"
                        style="width:100%; border:1px solid #cbd5e1; border-radius:8px; padding:8px 10px; background:#fff;">
                        <option value="" data-target="">Choose package...</option>
                        @foreach ($packages as $p)
                            <option value="{{ $p->id }}" data-target="{{ $p->target_type }}">
                                {{ $p->name }} ({{ ucfirst($p->target_type) }}) — {{ $p->duration_days }} days —
                                ₹{{ number_format((float) $p->price, 2) }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label style="display:block; font-size:0.8rem; font-weight:600; margin-bottom:4px;">
                        Target product <span style="font-weight:400; color:#64748b;">(For product packages)</span>
                    </label>
                    <select name="product_id" :disabled="targetType === 'business'"
                        style="width:100%; border:1px solid #cbd5e1; border-radius:8px; padding:8px 10px; background:#fff;">
                        <option value="">Entire business / None</option>
                        @foreach ($products as $product)
                            <option value="{{ $product->id }}">{{ $product->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label style="display:block; font-size:0.8rem; font-weight:600; margin-bottom:4px;">Payment
                        reference (UTR / Txn ID)</label>
                    <input name="payment_reference" maxlength="150" placeholder="e.g. UPI Ref 4123891023"
                        style="width:100%; border:1px solid #cbd5e1; border-radius:8px; padding:8px 10px; background:#fff;">
                </div>

                <div>
                    <button type="submit" class="g-btn g-btn-primary" style="width:100%; padding:9px 16px;">
                        Submit promotion request
                    </button>
                </div>
            </form>
        </section>

        {{-- Promotions History Table --}}
        <section class="g-panel"
            style="padding:1.5rem; border-radius:12px; background:var(--g-panel-bg, #fff); border:1px solid var(--g-border, #e2e8f0);">
            <h2 style="font-size:1.15rem; font-weight:700; margin:0 0 1rem; color:#0f172a;">Your promotion campaigns
            </h2>
            <div class="g-table-wrap" style="overflow-x:auto;">
                <table class="g-table" style="width:100%; border-collapse:collapse; text-align:left; font-size:0.9rem;">
                    <thead>
                        <tr style="border-bottom:2px solid #e2e8f0; color:#64748b; font-size:0.8rem;">
                            <th style="padding:10px 12px;">Package</th>
                            <th style="padding:10px 12px;">Target</th>
                            <th style="padding:10px 12px;">Payment Ref</th>
                            <th style="padding:10px 12px;">Status</th>
                            <th style="padding:10px 12px;">Schedule</th>
                            <th style="padding:10px 12px;">Views / Clicks</th>
                            <th style="padding:10px 12px;">Admin Note</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($promotions as $p)
                            @php
                                $badge = match ($p->status) {
                                    'active' => 'background:#dcfce7; color:#166534; border:1px solid #bbf7d0;',
                                    'pending' => 'background:#fef3c7; color:#92400e; border:1px solid #fde68a;',
                                    'rejected' => 'background:#fee2e2; color:#991b1b; border:1px solid #fecaca;',
                                    default => 'background:#f1f5f9; color:#475569; border:1px solid #cbd5e1;',
                                };
                            @endphp
                            <tr style="border-bottom:1px solid #f1f5f9;">
                                <td style="padding:12px;">
                                    <strong>{{ $p->package?->name ?? '—' }}</strong>
                                    @if ($p->package)
                                        <div style="font-size:0.75rem; color:#64748b;">{{ $p->package->duration_days }}
                                            days · ₹{{ number_format((float) $p->package->price, 2) }}</div>
                                    @endif
                                </td>
                                <td style="padding:12px;">
                                    @if ($p->product)
                                        <span
                                            style="font-size:0.7rem; background:#eff6ff; color:#1d4ed8; padding:2px 6px; border-radius:4px; font-weight:600;">Product</span>
                                        <div style="margin-top:2px;">{{ $p->product->name }}</div>
                                    @else
                                        <span
                                            style="font-size:0.7rem; background:#f3e8ff; color:#6b21a8; padding:2px 6px; border-radius:4px; font-weight:600;">Business</span>
                                        <div style="margin-top:2px;">{{ $business->name }}</div>
                                    @endif
                                </td>
                                <td style="padding:12px;">
                                    <code>{{ $p->payment_reference ?: '—' }}</code>
                                    <div
                                        style="font-size:0.7rem; color:{{ $p->payment_status === 'verified' ? '#166534' : '#64748b' }};">
                                        {{ ucfirst($p->payment_status) }}
                                    </div>
                                </td>
                                <td style="padding:12px;">
                                    <span
                                        style="font-size:0.75rem; font-weight:600; padding:3px 8px; border-radius:999px; {{ $badge }}">
                                        {{ ucfirst($p->status) }}
                                    </span>
                                </td>
                                <td style="padding:12px; font-size:0.85rem; color:#334155;">
                                    @if ($p->starts_at)
                                        {{ $p->starts_at->format('d M Y') }} → {{ $p->ends_at?->format('d M Y') }}
                                    @else
                                        <span style="color:#94a3b8;">Awaiting approval</span>
                                    @endif
                                </td>
                                <td style="padding:12px;">
                                    <strong>{{ number_format((int) $p->impressions) }}</strong> views /
                                    <strong>{{ number_format((int) $p->clicks) }}</strong> clicks
                                </td>
                                <td style="padding:12px; font-size:0.85rem; color:#64748b;">
                                    {{ $p->review_note ?: '—' }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" style="padding:24px; text-align:center; color:#64748b;">No promotion
                                    requests submitted yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div style="margin-top:1.25rem;">
                {{ $promotions->links() }}
            </div>
        </section>
    </div>
</x-layouts::app>
