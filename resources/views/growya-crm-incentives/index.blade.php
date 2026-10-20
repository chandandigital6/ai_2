<x-layouts::app :title="'CRM Incentives'">
    @php
        $badge = [
            'pending' => 'bg-amber-100 text-amber-800',
            'approved' => 'bg-emerald-100 text-emerald-800',
            'paid' => 'bg-indigo-100 text-indigo-800',
            'rejected' => 'bg-red-100 text-red-800',
        ];
        $basisLabel = [
            'crm_conversion' => 'CRM conversion',
            'manually_verified_sale' => 'Verified paid sale',
        ];
        $money = fn($v) => '₹' . number_format((float) $v, 2);
        $fmtDate = fn($d) => $d ? \Illuminate\Support\Carbon::parse($d)->format('d M Y H:i') : '—';
        $activeRules = $rules->where('active', true);
        $canCreateClaim = $manager && $activeRules->isNotEmpty() && $staff->isNotEmpty();
    @endphp

    <div class="mx-auto max-w-7xl space-y-6 px-4 py-8">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h1 class="text-2xl font-bold">Staff Incentives & Targets</h1>
                <p class="text-sm text-zinc-500">CRM conversion rewards and separately verified paid-sale claims</p>
            </div>
            @if ($manager)
                <div class="flex gap-2">
                    <a class="rounded-lg bg-zinc-800 px-4 py-2 text-white"
                        href="{{ route('growya.crm.incentives.report', $business) }}">Targets & Leaderboard</a>
                    <a class="rounded-lg border px-4 py-2"
                        href="{{ route('growya.crm.incentives.export', $business) }}">Export CSV</a>
                </div>
            @endif
        </div>

        @if (session('success'))
            <div class="rounded-xl bg-emerald-50 p-4 text-emerald-800">{{ session('success') }}</div>
        @endif
        @if ($errors->any())
            <div class="rounded-xl bg-red-50 p-4 text-red-800">{{ $errors->first() }}</div>
        @endif

        <div class="grid grid-cols-2 gap-3 md:grid-cols-4">
            @foreach (['pending' => 'Pending', 'approved' => 'Approved', 'paid' => 'Paid', 'rejected' => 'Rejected'] as $key => $label)
                <div class="rounded-xl border bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
                    <div class="text-xs text-zinc-500">{{ $label }}</div>
                    <div class="text-xl font-bold">{{ $money($summary->get($key)->amount ?? 0) }}</div>
                    <div class="text-xs text-zinc-500">{{ (int) ($summary->get($key)->total ?? 0) }} claims</div>
                </div>
            @endforeach
        </div>

        @if ($manager)
            <div class="grid gap-4 lg:grid-cols-2">
                {{-- ===== Rules ===== --}}
                <section class="space-y-3 rounded-xl border bg-white p-5 dark:border-zinc-700 dark:bg-zinc-900">
                    <h2 class="font-bold">Create Incentive Rule</h2>
                    <form class="grid gap-3" action="{{ route('growya.crm.incentives.rule', $business) }}"
                        method="POST">
                        @csrf
                        <input name="name" required maxlength="120" placeholder="Rule name"
                            value="{{ old('name') }}" class="rounded-lg border p-2 dark:bg-zinc-800" />
                        <select name="basis" required class="rounded-lg border p-2 dark:bg-zinc-800">
                            <option value="crm_conversion" @selected(old('basis') === 'crm_conversion')>CRM Converted Lead (not
                                verified sale)</option>
                            <option value="manually_verified_sale" @selected(old('basis') === 'manually_verified_sale')>Manually
                                verified paid sale</option>
                        </select>
                        <select name="calculation" required class="rounded-lg border p-2 dark:bg-zinc-800">
                            <option value="fixed" @selected(old('calculation') === 'fixed')>Fixed INR</option>
                            <option value="percentage" @selected(old('calculation') === 'percentage')>Percentage of supplied base
                                amount</option>
                        </select>
                        <input name="amount" type="number" step="0.01" min="0.01" required
                            value="{{ old('amount') }}" placeholder="Amount (INR) or percentage"
                            class="rounded-lg border p-2 dark:bg-zinc-800" />
                        <button class="rounded-lg bg-emerald-700 p-2 text-white">Save Rule</button>
                    </form>

                    <div class="space-y-2">
                        @forelse($rules as $rule)
                            <div
                                class="flex items-center justify-between gap-3 border-t pt-2 text-sm dark:border-zinc-700">
                                <div>
                                    <b>{{ $rule->name }}</b>
                                    <div class="text-xs text-zinc-500">
                                        {{ $basisLabel[$rule->basis] ?? $rule->basis }} ·
                                        {{ $rule->calculation === 'percentage' ? rtrim(rtrim(number_format((float) $rule->amount, 2), '0'), '.') . '%' : $money($rule->amount) }}
                                        ·
                                        <span class="{{ $rule->active ? 'text-emerald-700' : 'text-zinc-400' }}">
                                            {{ $rule->active ? 'Active' : 'Inactive' }}</span>
                                    </div>
                                </div>
                                <form method="POST"
                                    action="{{ route('growya.crm.incentives.rule.toggle', [$business, $rule->id]) }}">
                                    @csrf @method('PATCH')
                                    <button
                                        class="rounded border px-2 py-1">{{ $rule->active ? 'Disable' : 'Enable' }}</button>
                                </form>
                            </div>
                        @empty
                            <p class="text-sm text-zinc-500">Create a rule first.</p>
                        @endforelse
                    </div>
                </section>

                {{-- ===== Claim ===== --}}
                <section class="space-y-3 rounded-xl border bg-white p-5 dark:border-zinc-700 dark:bg-zinc-900">
                    <h2 class="font-bold">Create Pending Incentive Claim</h2>
                    <p class="text-xs text-zinc-500">Owner must review the supporting payment reference before approving
                        paid-sale incentives.</p>

                    @if ($canCreateClaim)
                        <form class="grid gap-3" method="POST"
                            action="{{ route('growya.crm.incentives.claim', $business) }}">
                            @csrf
                            <label class="text-sm">Active Rule
                                <select name="rule_id" required
                                    class="mt-1 block w-full rounded-lg border p-2 dark:bg-zinc-800">
                                    @foreach ($activeRules as $r)
                                        <option value="{{ $r->id }}" @selected((string) old('rule_id') === (string) $r->id)>
                                            {{ $r->name }} · {{ $basisLabel[$r->basis] ?? $r->basis }}</option>
                                    @endforeach
                                </select>
                            </label>
                            <label class="text-sm">Staff
                                <select name="staff_user_id" required
                                    class="mt-1 block w-full rounded-lg border p-2 dark:bg-zinc-800">
                                    @foreach ($staff as $s)
                                        <option value="{{ $s->id }}" @selected((string) old('staff_user_id') === (string) $s->id)>
                                            {{ $s->name }}</option>
                                    @endforeach
                                </select>
                            </label>
                            <input name="contact_id" type="number" min="1" value="{{ old('contact_id') }}"
                                placeholder="Assigned CRM Contact ID (required for conversion)"
                                class="rounded-lg border p-2 dark:bg-zinc-800" />
                            <input name="base_amount" type="number" step="0.01" min="0"
                                value="{{ old('base_amount') }}"
                                placeholder="Verified paid amount / percentage base (INR)"
                                class="rounded-lg border p-2 dark:bg-zinc-800" />
                            <input name="source_reference" maxlength="160" value="{{ old('source_reference') }}"
                                placeholder="Payment / sale reference (required for paid sale)"
                                class="rounded-lg border p-2 dark:bg-zinc-800" />
                            <textarea name="note" maxlength="2000" rows="2" placeholder="Evidence and verification notes"
                                class="rounded-lg border p-2 dark:bg-zinc-800">{{ old('note') }}</textarea>
                            <button class="rounded-lg bg-emerald-700 p-2 text-white">Create Pending Claim</button>
                        </form>
                    @else
                        <p class="rounded-lg bg-amber-50 p-3 text-sm text-amber-800">
                            @if ($activeRules->isEmpty())
                                Pehle ek active incentive rule banaiye.
                            @else
                                Claim banane ke liye business mein kam se kam ek active staff member chahiye.
                            @endif
                        </p>
                    @endif
                </section>
            </div>
        @endif

        {{-- ===== Claims ===== --}}
        <div class="space-y-4 rounded-xl border bg-white p-5 dark:border-zinc-700 dark:bg-zinc-900">
            <form method="GET" class="flex flex-wrap items-end gap-2">
                <label class="text-sm">Status
                    <select name="status" class="block rounded border p-2 dark:bg-zinc-800">
                        <option value="">All</option>
                        @foreach (['pending', 'approved', 'rejected', 'paid'] as $status)
                            <option value="{{ $status }}" @selected(request('status') === $status)>{{ ucfirst($status) }}
                            </option>
                        @endforeach
                    </select>
                </label>
                @if ($manager)
                    <label class="text-sm">Staff
                        <select name="staff_id" class="block rounded border p-2 dark:bg-zinc-800">
                            <option value="">All</option>
                            @foreach ($staff as $s)
                                <option value="{{ $s->id }}" @selected((string) request('staff_id') === (string) $s->id)>{{ $s->name }}
                                </option>
                            @endforeach
                        </select>
                    </label>
                @endif
                <button class="rounded-lg bg-zinc-800 px-4 py-2 text-white">Filter</button>
                @if (request()->hasAny(['status', 'staff_id']))
                    <a href="{{ route('growya.crm.incentives.index', $business) }}"
                        class="px-2 py-2 text-sm underline">Clear</a>
                @endif
            </form>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead>
                        <tr class="border-b dark:border-zinc-700">
                            <th class="p-3">Staff</th>
                            <th class="p-3">Basis / Lead</th>
                            <th class="p-3">Amount</th>
                            <th class="p-3">Status</th>
                            <th class="p-3">Reference</th>
                            <th class="p-3">Created</th>
                            @if ($manager)
                                <th class="p-3">Review / Payment</th>
                            @endif
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($claims as $c)
                            <tr class="border-b align-top dark:border-zinc-700">
                                <td class="p-3">{{ $c->staff_name }}</td>
                                <td class="p-3">
                                    {{ $basisLabel[$c->basis] ?? str_replace('_', ' ', $c->basis) }}
                                    <div class="text-xs text-zinc-500">{{ $c->contact_name ?? '—' }}</div>
                                </td>
                                <td class="p-3 font-semibold">{{ $money($c->incentive_amount) }}</td>
                                <td class="p-3">
                                    <span
                                        class="rounded-full px-2 py-1 text-xs font-medium {{ $badge[$c->status] ?? 'bg-zinc-100 text-zinc-800' }}">{{ ucfirst($c->status) }}</span>
                                </td>
                                <td class="p-3 text-xs">
                                    {{ $c->source_reference ?? '—' }}
                                    @if ($c->paid_at)
                                        <div>Paid ref: {{ $c->payment_reference }}</div>
                                        <div class="text-zinc-500">{{ $fmtDate($c->paid_at) }}</div>
                                    @endif
                                </td>
                                <td class="p-3 text-xs text-zinc-500">{{ $fmtDate($c->created_at) }}</td>
                                @if ($manager)
                                    <td class="space-y-2 p-3">
                                        @if ($c->status === 'pending')
                                            <form method="POST"
                                                action="{{ route('growya.crm.incentives.review', [$business, $c->id]) }}"
                                                class="space-y-2">
                                                @csrf @method('PATCH')
                                                @if ($c->basis === 'manually_verified_sale')
                                                    <label class="block text-xs">
                                                        <input type="checkbox" name="payment_verified"
                                                            value="1">
                                                        I independently verified this payment
                                                    </label>
                                                @endif
                                                <input name="note" maxlength="2000"
                                                    class="w-full rounded border p-1 dark:bg-zinc-800"
                                                    placeholder="Review note" />
                                                <div class="flex gap-2">
                                                    <button name="decision" value="approve"
                                                        class="rounded bg-emerald-700 px-3 py-1 text-white">Approve</button>
                                                    <button name="decision" value="reject"
                                                        class="rounded bg-red-700 px-3 py-1 text-white">Reject</button>
                                                </div>
                                            </form>
                                        @elseif($c->status === 'approved')
                                            <form method="POST"
                                                action="{{ route('growya.crm.incentives.pay', [$business, $c->id]) }}"
                                                class="flex flex-wrap gap-1">
                                                @csrf @method('PATCH')
                                                <input name="payment_reference" maxlength="160" required
                                                    placeholder="Actual payout reference"
                                                    class="rounded border p-1 dark:bg-zinc-800">
                                                <button class="rounded bg-indigo-700 px-2 py-1 text-white">Mark
                                                    Paid</button>
                                            </form>
                                        @else
                                            —
                                        @endif
                                    </td>
                                @endif
                            </tr>
                        @empty
                            <tr>
                                <td class="p-4 text-zinc-500" colspan="{{ $manager ? 7 : 6 }}">No incentive claims.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            {{ $claims->links() }}
        </div>

        @if ($manager)
            <section class="space-y-2 rounded-xl border bg-white p-5 dark:border-zinc-700 dark:bg-zinc-900">
                <h2 class="font-bold">Latest Audit History</h2>
                @forelse($audits as $a)
                    <p class="border-b py-2 text-sm dark:border-zinc-700">
                        Claim #{{ $a->claim_id }} · <b>{{ $a->action }}</b> · {{ $a->actor_name ?? 'System' }} ·
                        {{ $fmtDate($a->created_at) }}
                        @if ($a->note)
                            <span class="text-zinc-500">— {{ $a->note }}</span>
                        @endif
                    </p>
                @empty
                    <p class="text-sm text-zinc-500">No audit history.</p>
                @endforelse
            </section>
        @endif
    </div>
</x-layouts::app>
