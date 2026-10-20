<x-layouts::app :title="'Business Finance'">
    <div class="mx-auto max-w-7xl space-y-6 px-4 py-8">
        <header>
            <h1 class="text-2xl font-bold">Business Expenses & Finance — {{ $business->name }}</h1>
            <p class="text-sm text-zinc-500">Indicative financial overview. Not an audited P&L or tax statement. Paid
                orders use order creation date, not payment settlement date.</p>
        </header>
        @if (session('success'))
            <div class="rounded-xl bg-emerald-100 p-4 text-emerald-900">{{ session('success') }}</div>
        @endif
        @if ($errors->any())
            <div class="rounded-xl bg-red-100 p-4 text-red-800">{{ $errors->first() }}</div>
        @endif
        <form method="GET" class="flex flex-wrap items-end gap-3 rounded-xl border p-4">
            <label class="grid gap-1">From<input class="rounded-lg border p-2" type="date" name="from"
                    value="{{ $from }}"></label>
            <label class="grid gap-1">To<input class="rounded-lg border p-2" type="date" name="to"
                    value="{{ $to }}"></label>
            <button class="rounded-lg bg-emerald-700 px-4 py-2 text-white">Filter</button>
            <a class="rounded-lg border px-4 py-2"
                href="{{ route('growya.finance.export', ['business' => $business->id, 'from' => $from, 'to' => $to]) }}">Export
                CSV</a>
        </form>
        <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
            @foreach (['paidOrderRevenue' => 'Paid Order Total (indicative)', 'manualIncome' => 'Other Income (manual)', 'manualExpenses' => 'Expenses (manual)', 'paidIncentives' => 'Paid Staff Incentives', 'totalIncome' => 'Total Indicative Inflow', 'indicativeBalance' => 'Indicative Balance'] as $key => $label)
                <div class="rounded-xl border p-5">
                    <p class="text-sm text-zinc-500">{{ $label }}</p>
                    <p class="mt-2 text-2xl font-bold">₹{{ number_format($summary[$key], 2) }}</p>
                </div>
            @endforeach
        </div>
        <p class="rounded-xl bg-amber-50 p-4 text-sm text-amber-900">Do not manually record paid-order receipts or paid
            staff incentives again. The order total may include tax, returns and other amounts; partial refunds, cost of
            goods, GST adjustments, outstanding invoices and payment settlement timing are NOT reconciled here. Verify
            order payment_status before trusting figures.</p>
        <div class="grid gap-5 lg:grid-cols-2">
            <section class="rounded-xl border p-5">
                <h2 class="mb-4 font-bold">Add Income / Expense</h2>
                <form method="POST" action="{{ route('growya.finance.store', $business->id) }}" class="grid gap-3">@csrf
                    <select name="kind" required class="rounded-lg border p-2">
                        <option value="expense">Expense</option>
                        <option value="income">Other Income</option>
                    </select>
                    <input name="amount" type="number" min="0.01" step="0.01" required
                        placeholder="Amount in INR" class="rounded-lg border p-2">
                    <input name="entry_date" type="date" value="{{ now()->toDateString() }}" required
                        class="rounded-lg border p-2">
                    <select name="category_id" class="rounded-lg border p-2">
                        <option value="">No category</option>
                        @foreach ($categories as $c)
                            <option value="{{ $c->id }}">{{ ucfirst($c->kind) }} — {{ $c->name }}
                            </option>
                        @endforeach
                    </select>
                    <select name="payment_method" required class="rounded-lg border p-2">
                        @foreach (['cash', 'upi', 'bank', 'card', 'cheque', 'other'] as $method)
                            <option value="{{ $method }}">{{ strtoupper($method) }}</option>
                        @endforeach
                    </select>
                    <input name="reference" maxlength="140" placeholder="Reference (optional)"
                        class="rounded-lg border p-2">
                    <textarea name="description" maxlength="500" placeholder="Description" class="rounded-lg border p-2"></textarea>
                    <button class="rounded-lg bg-emerald-700 px-4 py-2 text-white">Save Entry</button>
                </form>
            </section>
            <section class="rounded-xl border p-5">
                <h2 class="mb-4 font-bold">Create Category</h2>
                <form method="POST" action="{{ route('growya.finance.categories.store', $business->id) }}"
                    class="grid gap-3">@csrf
                    <input name="name" required maxlength="100" placeholder="Salary, Rent, Electricity, Marketing..."
                        class="rounded-lg border p-2">
                    <select name="kind" class="rounded-lg border p-2">
                        <option value="expense">Expense</option>
                        <option value="income">Income</option>
                    </select>
                    <button class="rounded-lg bg-emerald-700 px-4 py-2 text-white">Save Category</button>
                </form>
                <h3 class="mt-6 font-bold">Monthly Manual Entries</h3>
                <div class="mt-2 max-h-72 overflow-y-auto text-sm">
                    @forelse($months as $m)
                        <p class="border-b py-2">{{ $m->month }} · {{ ucfirst($m->kind) }} ·
                        ₹{{ number_format($m->amount, 2) }}</p>@empty<p>No manual entries in selected period.</p>
                    @endforelse
                </div>
            </section>
        </div>
        <section class="rounded-xl border p-5">
            <h2 class="mb-4 text-lg font-bold">Manual Entry History</h2>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead>
                        <tr class="border-b">
                            <th class="p-2">Date</th>
                            <th class="p-2">Type</th>
                            <th class="p-2">Category</th>
                            <th class="p-2">Amount</th>
                            <th class="p-2">Method</th>
                            <th class="p-2">Reference / Notes</th>
                            <th class="p-2">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($entries as $e)
                            <tr class="border-b">
                                <td class="p-2">{{ $e->entry_date }}</td>
                                <td class="p-2">{{ ucfirst($e->kind) }}</td>
                                <td class="p-2">{{ $e->category_name ?? '—' }}</td>
                                <td class="p-2">₹{{ number_format($e->amount, 2) }}</td>
                                <td class="p-2">{{ strtoupper($e->payment_method) }}</td>
                                <td class="p-2">{{ $e->reference }} {{ $e->description }}</td>
                                <td class="p-2">
                                    <details>
                                        <summary class="cursor-pointer">Edit</summary>
                                        <form method="POST"
                                            action="{{ route('growya.finance.update', [$business->id, $e->id]) }}"
                                            class="grid min-w-48 gap-2 py-2">@csrf @method('PATCH')<input
                                                type="number" name="amount" step="0.01" min="0.01"
                                                value="{{ $e->amount }}" required class="border p-1"><input
                                                type="date" name="entry_date" value="{{ $e->entry_date }}"
                                                required class="border p-1"><select name="payment_method"
                                                class="border p-1">
                                                @foreach (['cash', 'upi', 'bank', 'card', 'cheque', 'other'] as $pm)
                                                    <option value="{{ $pm }}" @selected($e->payment_method === $pm)>
                                                        {{ $pm }}</option>
                                                @endforeach
                                            </select><input name="reference" value="{{ $e->reference }}"
                                                placeholder="Reference" class="border p-1"><input name="description"
                                                value="{{ $e->description }}" placeholder="Description"
                                                class="border p-1"><button
                                                class="bg-emerald-700 p-1 text-white">Update</button></form>
                                    </details>
                                    <form method="POST"
                                        action="{{ route('growya.finance.destroy', [$business->id, $e->id]) }}"
                                        onsubmit="return confirm('Archive this entry?')">@csrf @method('DELETE')<button
                                            class="text-red-700">Archive</button></form>
                                </td>
                            </tr>
                        @empty<tr>
                                <td colspan="7" class="p-5 text-center">No entries</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="mt-4">{{ $entries->links() }}</div>
        </section>
    </div>
</x-layouts::app>
