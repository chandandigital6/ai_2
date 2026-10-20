<x-layouts::app title="Manage Paid Promotions">
    <div class="g-page">
        <div class="g-page-heading" style="display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:1.5rem; flex-wrap:wrap; gap:1rem;">
            <div>
                <p class="g-kicker" style="font-size:0.8rem; text-transform:uppercase; letter-spacing:0.05em; color:#64748b; margin:0;">Administration</p>
                <h1 style="font-size:1.75rem; font-weight:700; margin:0.25rem 0;">Paid promotion requests</h1>
                <p style="color:#64748b; margin:0;">Verify manual payment references in your bank/UPI records before approving campaigns.</p>
            </div>
            <a class="g-btn g-btn-secondary" href="{{ route('growya.promotions.packages') }}">Manage packages</a>
        </div>

        @if (session('success'))
            <div class="g-alert g-tone-green" style="padding:12px 16px; border-radius:8px; margin-bottom:1.25rem; background:#dcfce7; color:#166534;">
                {{ session('success') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="g-alert" style="padding:12px 16px; border-radius:8px; margin-bottom:1.25rem; background:#fee2e2; color:#991b1b;">
                {{ $errors->first() }}
            </div>
        @endif

        {{-- Filter Bar --}}
        <div class="g-panel" style="padding:1rem 1.25rem; margin-bottom:1.5rem; border-radius:12px; background:var(--g-panel-bg, #fff); border:1px solid var(--g-border, #e2e8f0);">
            <form method="GET" style="display:flex; gap:0.75rem; align-items:center; flex-wrap:wrap;">
                <label style="font-size:0.875rem; font-weight:500;">Filter status:</label>
                <select name="status" style="padding:0.45rem 0.85rem; border-radius:8px; border:1px solid #cbd5e1; font-size:0.9rem; background:#fff;">
                    <option value="">All statuses</option>
                    @foreach (['pending', 'active', 'rejected', 'expired'] as $st)
                        <option value="{{ $st }}" @selected(request('status') === $st)>{{ ucfirst($st) }}</option>
                    @endforeach
                </select>
                <button type="submit" class="g-btn g-btn-secondary" style="padding:0.45rem 1rem;">Filter</button>
            </form>
        </div>

        {{-- Promotion Requests List --}}
        <div style="display:flex; flex-direction:column; gap:1.25rem;">
            @forelse ($promotions as $p)
                @php
                    $badge = match($p->status) {
                        'active' => 'background:#dcfce7; color:#166534; border:1px solid #bbf7d0;',
                        'pending' => 'background:#fef3c7; color:#92400e; border:1px solid #fde68a;',
                        'rejected' => 'background:#fee2e2; color:#991b1b; border:1px solid #fecaca;',
                        default => 'background:#f1f5f9; color:#475569; border:1px solid #cbd5e1;',
                    };
                @endphp
                <section class="g-panel" style="padding:1.5rem; border-radius:12px; background:var(--g-panel-bg, #fff); border:1px solid var(--g-border, #e2e8f0);">
                    <div style="display:flex; justify-content:space-between; align-items:flex-start; flex-wrap:wrap; gap:1rem; border-bottom:1px solid #f1f5f9; padding-bottom:1rem; margin-bottom:1rem;">
                        <div>
                            <div style="display:flex; align-items:center; gap:0.75rem;">
                                <h2 style="font-size:1.15rem; font-weight:700; margin:0;">#{{ $p->id }} · {{ $p->business?->name ?? 'Removed Business' }}</h2>
                                <span style="font-size:0.75rem; font-weight:600; padding:2px 8px; border-radius:999px; text-transform:uppercase; {{ $badge }}">
                                    {{ $p->status }}
                                </span>
                            </div>
                            <p style="margin:0.25rem 0 0; font-size:0.85rem; color:#64748b;">
                                Requested by: {{ $p->requester?->name ?? 'User #'.$p->requested_by }} · {{ $p->created_at?->format('d M Y, h:i A') }}
                            </p>
                        </div>

                        <div style="text-align:right;">
                            <div style="font-size:1.2rem; font-weight:700; color:#0f172a;">₹{{ number_format((float)($p->package?->price ?? 0), 2) }}</div>
                            <small style="color:#64748b;">{{ $p->package?->name }} ({{ $p->package?->duration_days }} days)</small>
                        </div>
                    </div>

                    <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(200px, 1fr)); gap:1rem; font-size:0.875rem; background:#f8fafc; padding:12px 16px; border-radius:8px; margin-bottom:1rem;">
                        <div>
                            <span style="color:#64748b; display:block; font-size:0.75rem;">Promotion Target</span>
                            <strong>{{ $p->product ? 'Product: '.$p->product->name : 'Business Profile' }}</strong>
                        </div>
                        <div>
                            <span style="color:#64748b; display:block; font-size:0.75rem;">Payment Reference</span>
                            <code>{{ $p->payment_reference ?: 'Not provided' }}</code> ({{ ucfirst($p->payment_status) }})
                        </div>
                        <div>
                            <span style="color:#64748b; display:block; font-size:0.75rem;">Schedule</span>
                            <strong>{{ $p->starts_at ? $p->starts_at->format('d M Y').' → '.$p->ends_at?->format('d M Y') : 'Not started' }}</strong>
                        </div>
                        <div>
                            <span style="color:#64748b; display:block; font-size:0.75rem;">Performance</span>
                            <strong>{{ $p->impressions }}</strong> views · <strong>{{ $p->clicks }}</strong> clicks
                        </div>
                    </div>

                    @if ($p->status === 'pending')
                        <form method="POST" action="{{ route('growya.promotions.review', $p) }}" style="display:grid; gap:1rem; grid-template-columns:repeat(auto-fit, minmax(200px, 1fr)); align-items:flex-end; padding-top:0.5rem;">
                            @csrf
                            @method('PATCH')

                            <div>
                                <label style="display:block; font-size:0.8rem; font-weight:600; margin-bottom:4px;">Decision</label>
                                <select name="decision" required style="width:100%; border:1px solid #cbd5e1; border-radius:8px; padding:8px; background:#fff;">
                                    <option value="approve">Approve & start campaign</option>
                                    <option value="reject">Reject request</option>
                                </select>
                            </div>

                            <div>
                                <label style="display:block; font-size:0.8rem; font-weight:600; margin-bottom:4px;">Review note (Optional)</label>
                                <input name="review_note" maxlength="2000" placeholder="Reason or confirmation note" style="width:100%; border:1px solid #cbd5e1; border-radius:8px; padding:8px; background:#fff;">
                            </div>

                            <div style="display:flex; align-items:center; gap:8px; padding-bottom:10px;">
                                <input type="checkbox" id="verify_pay_{{ $p->id }}" name="payment_verified" value="1" style="width:18px; height:18px;">
                                <label for="verify_pay_{{ $p->id }}" style="font-size:0.82rem; font-weight:600;">Payment verified in bank/UPI</label>
                            </div>

                            <div>
                                <button type="submit" class="g-btn g-btn-primary" style="width:100%; padding:9px;" onclick="return confirm('Confirm promotion decision?')">
                                    Save review
                                </button>
                            </div>
                        </form>
                    @elseif ($p->review_note)
                        <p style="font-size:0.85rem; color:#475569; margin:0.5rem 0 0;"><strong>Review Note:</strong> {{ $p->review_note }}</p>
                    @endif
                </section>
            @empty
                <section class="g-panel" style="padding:2.5rem; text-align:center; color:#64748b; border-radius:12px;">
                    No paid promotion requests found.
                </section>
            @endforelse
        </div>

        <div style="margin-top:1.5rem;">
            {{ $promotions->links() }}
        </div>
    </div>
</x-layouts::app>
