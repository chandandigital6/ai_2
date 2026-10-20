<x-layouts::app :title="__('Subscription requests')">
    <div class="g-page">
        <div class="g-page-heading" style="display:flex; justify-content:space-between; align-items:flex-start; margin-bottom: 1.5rem;">
            <div>
                <p class="g-kicker" style="font-size:0.8rem; text-transform:uppercase; letter-spacing:0.05em; color:var(--g-muted, #64748b); margin:0;">Administration</p>
                <h1 style="font-size:1.75rem; font-weight:700; margin:0.25rem 0;">Subscription requests</h1>
                <p style="color:var(--g-muted, #64748b); margin:0;">Verify payments and manage business subscription approvals.</p>
            </div>
            <a class="g-btn g-btn-secondary" href="{{ route('growya.plans.index') }}">Manage plans</a>
        </div>

        @if (session('success'))
            <div class="g-alert g-tone-green" style="padding:12px 16px; border-radius:8px; margin-bottom:1rem; background:#dcfce7; color:#166534;">
                {{ session('success') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="g-alert" style="padding:12px 16px; border-radius:8px; margin-bottom:1rem; background:#fee2e2; color:#991b1b;">
                {{ $errors->first() }}
            </div>
        @endif

        <div class="g-panel" style="padding:1rem 1.25rem; margin-bottom:1.5rem; border-radius:12px; background:var(--g-panel-bg, #fff); border:1px solid var(--g-border, #e2e8f0);">
            <form method="GET" style="display:flex; gap:0.75rem; align-items:center; flex-wrap:wrap;">
                <label style="font-size:0.875rem; font-weight:500;">Filter by status:</label>
                <select name="status" style="padding:0.45rem 0.85rem; border-radius:8px; border:1px solid #cbd5e1; font-size:0.9rem;">
                    <option value="">All statuses</option>
                    @foreach (['requested', 'active', 'rejected', 'expired', 'superseded'] as $st)
                        <option value="{{ $st }}" @selected(request('status') === $st)>{{ ucfirst($st) }}</option>
                    @endforeach
                </select>
                <button type="submit" class="g-btn g-btn-secondary" style="padding:0.45rem 1rem;">Filter</button>
                @if(request('status'))
                    <a href="{{ route('growya.subscriptions.admin') }}" style="font-size:0.85rem; color:#64748b; text-decoration:underline;">Reset</a>
                @endif
            </form>
        </div>

        <div style="display:flex; flex-direction:column; gap:1.25rem;">
            @forelse($subscriptions as $sub)
                @php
                    $badgeColors = [
                        'requested'  => 'background:#fef3c7; color:#92400e; border:1px solid #fde68a;',
                        'active'     => 'background:#dcfce7; color:#166534; border:1px solid #bbf7d0;',
                        'rejected'   => 'background:#fee2e2; color:#991b1b; border:1px solid #fecaca;',
                        'expired'    => 'background:#f1f5f9; color:#475569; border:1px solid #cbd5e1;',
                        'superseded' => 'background:#f1f5f9; color:#64748b; border:1px solid #e2e8f0;',
                    ];
                $subPayment = $sub->subscription_payments->first();
                @endphp

                <section class="g-panel" style="padding:1.5rem; border-radius:12px; background:var(--g-panel-bg, #fff); border:1px solid var(--g-border, #e2e8f0);">
                    <div style="display:flex; justify-content:space-between; align-items:flex-start; flex-wrap:wrap; gap:0.5rem; border-bottom:1px solid #f1f5f9; padding-bottom:1rem; margin-bottom:1rem;">
                        <div>
                            <div style="display:flex; align-items:center; gap:0.75rem;">
                                <h2 style="font-size:1.15rem; font-weight:700; margin:0;">#{{ $sub->id }} · {{ $sub->business?->name ?? 'Business removed' }}</h2>
                                <span style="font-size:0.75rem; font-weight:600; padding:2px 8px; border-radius:999px; text-transform:uppercase; {{ $badgeColors[$sub->status] ?? '' }}">
                                    {{ $sub->status }}
                                </span>
                            </div>
                            <p style="margin:0.25rem 0 0; font-size:0.875rem; color:#64748b;">
                                Requested on: {{ $sub->created_at?->format('d M Y, h:i A') }}
                            </p>
                        </div>
                        <div style="text-align:right;">
                            <div style="font-size:1.25rem; font-weight:700; color:#0f172a;">₹{{ number_format((float) $sub->amount, 2) }}</div>
                            <small style="color:#64748b; text-transform:capitalize;">{{ $sub->plan?->name ?? 'Custom plan' }} ({{ $sub->billing_cycle }})</small>
                        </div>
                    </div>

                    {{-- Validity Info if Active --}}
                    @if($sub->starts_at || $sub->ends_at)
                        <div style="display:flex; gap:1.5rem; font-size:0.85rem; margin-bottom:1rem; color:#334155; background:#f8fafc; padding:8px 12px; border-radius:8px;">
                            <span><strong>Starts:</strong> {{ $sub->starts_at?->format('d M Y') ?? '—' }}</span>
                            <span><strong>Ends:</strong> {{ $sub->ends_at?->format('d M Y') ?? '—' }}</span>
                        </div>
                    @endif

                    {{-- Recorded Payment Details Section --}}
                    @if ($subPayment)
                        <div style="margin-top:0.75rem; background:#f8fafc; border:1px solid #e2e8f0; border-radius:10px; padding:12px 16px;">
                            <h4 style="font-size:0.8rem; text-transform:uppercase; color:#64748b; font-weight:700; margin:0 0 0.5rem;">Recorded Payment Details</h4>
                            <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(180px, 1fr)); gap:10px; font-size:0.85rem;">
                                <div><span style="color:#64748b;">Method:</span> <strong style="text-transform:capitalize;">{{ str_replace('_', ' ', $subPayment->payment_method) }}</strong></div>
                                <div><span style="color:#64748b;">Txn Ref:</span> <code>{{ $subPayment->transaction_id ?: 'N/A' }}</code></div>
                                <div><span style="color:#64748b;">Status:</span> <strong style="text-transform:capitalize;">{{ $subPayment->status }}</strong></div>
                                <div><span style="color:#64748b;">Paid at:</span> {{ $subPayment->paid_at?->format('d M Y, h:i A') ?? '—' }}</div>
                            </div>
                            @if(!empty($subPayment->metadata['note']))
                                <div style="margin-top:8px; font-size:0.85rem; border-top:1px dashed #cbd5e1; padding-top:6px;">
                                    <span style="color:#64748b;">Admin Note:</span> {{ $subPayment->metadata['note'] }}
                                </div>
                            @endif
                        </div>
                    @endif

                    {{-- Review Form --}}
                    @if ($sub->status === 'requested')
                        <div style="margin-top:1rem; padding-top:1rem; border-top:1px solid #f1f5f9;">
                            <form method="POST" action="{{ route('growya.subscriptions.review', $sub) }}" style="display:grid; gap:0.85rem; grid-template-columns:repeat(auto-fit, minmax(200px, 1fr)); align-items:flex-end;">
                                @csrf 
                                @method('PATCH')
                                
                                <div>
                                    <label style="display:block; font-size:0.8rem; font-weight:600; margin-bottom:4px;">Decision</label>
                                    <select name="decision" required style="width:100%; border:1px solid #cbd5e1; border-radius:8px; padding:8px;">
                                        <option value="approve">Approve & activate</option>
                                        <option value="reject">Reject</option>
                                    </select>
                                </div>

                                <div>
                                    <label style="display:block; font-size:0.8rem; font-weight:600; margin-bottom:4px;">Payment Method</label>
                                    <select name="payment_method" style="width:100%; border:1px solid #cbd5e1; border-radius:8px; padding:8px;">
                                        <option value="">Select (Required if approved)</option>
                                        <option value="upi">UPI</option>
                                        <option value="bank_transfer">Bank transfer</option>
                                        <option value="cash">Cash</option>
                                        <option value="complimentary">Complimentary / waived</option>
                                    </select>
                                </div>

                                <div>
                                    <label style="display:block; font-size:0.8rem; font-weight:600; margin-bottom:4px;">Transaction Reference</label>
                                    <input name="transaction_id" placeholder="UTR / Ref No." maxlength="150" style="width:100%; border:1px solid #cbd5e1; border-radius:8px; padding:8px;">
                                </div>

                                <div>
                                    <label style="display:block; font-size:0.8rem; font-weight:600; margin-bottom:4px;">Admin Note</label>
                                    <input name="note" placeholder="Optional internal note" maxlength="500" style="width:100%; border:1px solid #cbd5e1; border-radius:8px; padding:8px;">
                                </div>

                                <div>
                                    <button type="submit" class="g-btn g-btn-primary" style="width:100%; padding:9px;" onclick="return confirm('Confirm subscription decision?')">
                                        Save decision
                                    </button>
                                </div>
                            </form>
                        </div>
                    @endif
                </section>
            @empty
                <section class="g-panel" style="padding:2.5rem; text-align:center; color:#64748b; border-radius:12px; background:#fff; border:1px solid #e2e8f0;">
                    No subscription requests found.
                </section>
            @endforelse
        </div>

        <div style="margin-top:1.5rem;">
            {{ $subscriptions->links() }}
        </div>
    </div>
</x-layouts::app>