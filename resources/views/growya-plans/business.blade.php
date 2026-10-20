<x-layouts::app :title="__('Business subscriptions')" :business="$business">
    <div class="g-page">
        <div class="g-page-heading" style="margin-bottom:1.5rem;">
            <div>
                <p class="g-kicker"
                    style="font-size:0.8rem; text-transform:uppercase; letter-spacing:0.05em; color:#64748b; margin:0;">
                    {{ $business->name }}</p>
                <h1 style="font-size:1.75rem; font-weight:700; margin:0.25rem 0;">Subscription plans</h1>
                <p style="color:#64748b; margin:0;">Select a plan for your business or review your request status.</p>
            </div>
        </div>

        @if (session('success'))
            <div class="g-alert g-tone-green"
                style="padding:12px 16px; border-radius:8px; margin-bottom:1rem; background:#dcfce7; color:#166534;">
                {{ session('success') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="g-alert"
                style="padding:12px 16px; border-radius:8px; margin-bottom:1rem; background:#fee2e2; color:#991b1b;">
                {{ $errors->first() }}
            </div>
        @endif

        {{-- Available Plans --}}
        <section class="g-panel"
            style="padding:1.5rem; border-radius:12px; background:#fff; border:1px solid #e2e8f0; margin-bottom:2rem;">
            <h2 style="font-size:1.25rem; font-weight:700; margin-bottom:1rem;">Available plans</h2>
            <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(260px, 1fr)); gap:1.25rem;">
                @forelse($plans as $plan)
                    <div
                        style="border:1px solid #e2e8f0; border-radius:12px; padding:1.25rem; display:flex; flex-direction:column; justify-content:space-between; background:#fafafa;">
                        <div>
                            <h3 style="font-size:1.2rem; font-weight:700; margin:0 0 0.5rem; color:#0f172a;">
                                {{ $plan->name }}</h3>
                            <div style="margin:1rem 0;">
                                <div style="font-size:1.35rem; font-weight:800; color:#1e293b;">
                                    ₹{{ number_format((float) $plan->monthly_price, 2) }} <span
                                        style="font-size:0.85rem; font-weight:400; color:#64748b;">/ mo</span>
                                </div>
                                <div style="font-size:0.9rem; color:#64748b; margin-top:2px;">
                                    ₹{{ number_format((float) $plan->yearly_price, 2) }} / year
                                </div>
                            </div>
                        </div>

                        <form method="POST" action="{{ route('growya.subscriptions.request', $business) }}"
                            style="margin-top:1rem;">
                            @csrf
                            <input type="hidden" name="plan_id" value="{{ $plan->id }}">
                            <div style="margin-bottom:0.75rem;">
                                <label
                                    style="display:block; font-size:0.8rem; font-weight:600; margin-bottom:4px;">Billing
                                    cycle</label>
                                <select name="billing_cycle" required
                                    style="width:100%; border:1px solid #cbd5e1; border-radius:8px; padding:7px; background:#fff;">
                                    <option value="monthly">Monthly billing</option>
                                    <option value="yearly">Yearly billing</option>
                                </select>
                            </div>
                            <button class="g-btn g-btn-primary" type="submit" style="width:100%; padding:9px;">
                                Request plan
                            </button>
                        </form>
                    </div>
                @empty
                    <p style="color:#64748b;">No active plans available right now.</p>
                @endforelse
            </div>
        </section>

        {{-- Subscription History --}}
        <section class="g-panel" style="padding:1.5rem; border-radius:12px; background:#fff; border:1px solid #e2e8f0;">
            <h2 style="font-size:1.25rem; font-weight:700; margin-bottom:1rem;">Subscription history</h2>
           
            <div class="g-table-wrap" style="overflow-x:auto;">
                <table class="g-table" style="width:100%; text-align:left; border-collapse:collapse;">
                    <thead>
                        <tr style="border-bottom:2px solid #e2e8f0; color:#64748b; font-size:0.85rem;">
                            <th style="padding:10px 12px;">Plan</th>
                            <th style="padding:10px 12px;">Cycle</th>
                            <th style="padding:10px 12px;">Amount</th>
                            <th style="padding:10px 12px;">Status</th>
                            <th style="padding:10px 12px;">Start date</th>
                            <th style="padding:10px 12px;">End date</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($history as $sub)
                            @php
                                $badgeStyle = match ($sub->status) {
                                    'active' => 'background:#dcfce7; color:#166534;',
                                    'requested' => 'background:#fef3c7; color:#92400e;',
                                    'rejected' => 'background:#fee2e2; color:#991b1b;',
                                    default => 'background:#f1f5f9; color:#475569;',
                                };
                            @endphp
                            <tr style="border-bottom:1px solid #f1f5f9; font-size:0.9rem;">
                                <td style="padding:12px;"><strong>{{ $sub->plan?->name ?? '—' }}</strong></td>
                                <td style="padding:12px; text-transform:capitalize;">{{ $sub->billing_cycle }}</td>
                                <td style="padding:12px;">₹{{ number_format((float) $sub->amount, 2) }}</td>
                                <td style="padding:12px;">
                                    <span
                                        style="font-size:0.75rem; font-weight:600; padding:3px 8px; border-radius:6px; {{ $badgeStyle }}">
                                        {{ $sub->status === 'active' && $sub->ends_at && $sub->ends_at->isPast() ? 'Expired' : ucfirst($sub->status) }}
                                    </span>
                                </td>
                                <td style="padding:12px;">{{ $sub->starts_at?->format('d M Y') ?? '—' }}</td>
                                <td style="padding:12px;">{{ $sub->ends_at?->format('d M Y') ?? '—' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" style="padding:20px; text-align:center; color:#64748b;">No
                                    subscription history found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </div>
</x-layouts::app>
