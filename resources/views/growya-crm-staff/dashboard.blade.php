<x-layouts::app :title="'Staff CRM Dashboard'">
    <div class="mx-auto max-w-7xl px-5 py-7 space-y-6">
        <h1 class="text-2xl font-bold">Staff CRM Dashboard — {{ $business->name }}</h1>
        @if (session('success'))
            <div class="rounded-lg bg-green-100 p-3 text-green-900">{{ session('success') }}</div>
        @endif
        @if ($errors->any())
            <div class="rounded-lg bg-red-100 p-3 text-red-900">{{ $errors->first() }}</div>
        @endif
        <div class="flex flex-wrap gap-3">
            <a class="rounded-lg bg-emerald-700 px-4 py-2 text-white"
                href="{{ route('growya.crm.index', $business) }}">Customer CRM</a>
            @if ($manager)
                <a class="rounded-lg bg-slate-800 px-4 py-2 text-white"
                    href="{{ route('growya.crm.staff.report', $business) }}">Staff Performance Report</a>
            @endif
        </div>
        <div class="grid gap-3 sm:grid-cols-3 lg:grid-cols-6">
            @foreach (['new', 'contacted', 'interested', 'converted', 'closed'] as $status)
                <div class="rounded-xl border p-4">
                    <div class="text-xs uppercase text-slate-500">{{ $status }}</div>
                    <div class="text-2xl font-bold">{{ $totals[$status] ?? 0 }}</div>
                </div>
            @endforeach
            <div class="rounded-xl border p-4">
                <div class="text-xs uppercase text-slate-500">Overdue/Due</div>
                <div class="text-2xl font-bold text-red-600">{{ $due }}</div>
            </div>
        </div>
        @if ($manager)
            <form method="GET" class="flex gap-3 items-end"><label>Assigned Staff<select
                        class="block rounded-lg border p-2" name="staff_id">
                        <option value="">All staff</option>
                        @foreach ($staff as $s)
                            <option value="{{ $s->id }}" @selected(request('staff_id') == $s->id)>{{ $s->name }}
                            </option>
                        @endforeach
                    </select>
                </label><button class="rounded-lg bg-emerald-700 p-2 text-white">Filter</button></form>
            <div class="rounded-xl border p-4 space-y-3">
                <h2 class="text-lg font-semibold">Set Staff Target</h2>
                <form method="POST" action="{{ route('growya.crm.staff.target', $business) }}"
                    class="grid gap-3 md:grid-cols-4">@csrf
                    <label>Staff<select name="user_id" required class="block w-full rounded-lg border p-2">
                            @foreach ($staff as $s)
                                <option value="{{ $s->id }}">{{ $s->name }}</option>
                            @endforeach
                        </select>
                    </label>
                    <label>Period<select name="period" class="block w-full rounded-lg border p-2">
                            <option value="daily">Daily</option>
                            <option value="weekly">Weekly</option>
                            <option value="monthly">Monthly</option>
                        </select></label>
                    <label>Starting date<input class="block w-full rounded-lg border p-2" type="date"
                            name="period_start" value="{{ now()->toDateString() }}" required></label>
                    <label>Call target<input class="block w-full rounded-lg border p-2" type="number" min="0"
                            name="calls_target" value="10" required></label>
                    <label>Follow-ups<input class="block w-full rounded-lg border p-2" type="number" min="0"
                            name="followups_target" value="10" required></label>
                    <label>Conversions<input class="block w-full rounded-lg border p-2" type="number" min="0"
                            name="conversions_target" value="2" required></label>
                    <div class="flex items-end"><button class="rounded-lg bg-emerald-700 px-4 py-2 text-white">Save
                            target</button></div>
                </form>
            </div>
        @endif
        <h2 class="text-lg font-semibold">Assigned Leads</h2>
        <div class="overflow-x-auto rounded-xl border">
            <table class="w-full text-left text-sm">
                <thead>
                    <tr class="border-b">
                        <th class="p-3">Customer</th>
                        <th class="p-3">Status</th>
                        <th class="p-3">Staff</th>
                        <th class="p-3">Follow-up</th>
                        <th class="p-3">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($leads as $c)
                        <tr class="border-b">
                            <td class="p-3">{{ $c->name }}</td>
                            <td class="p-3">{{ ucfirst($c->status) }}</td>
                            <td class="p-3">{{ $c->assignedUser?->name ?? 'Unassigned' }}</td>
                            <td class="p-3">{{ $c->next_follow_up_at?->format('d M Y H:i') ?? '—' }}</td>
                            <td class="p-3"><a class="text-emerald-700 underline"
                                    href="{{ route('growya.crm.staff.show', [$business, $c->id]) }}">Open</a></td>
                        </tr>
                    @empty<tr>
                            <td class="p-4" colspan="5">No leads assigned.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        {{ $leads->links() }}
        <h2 class="text-lg font-semibold">Recent Targets</h2>
        <div class="overflow-x-auto rounded-xl border">
            <table class="w-full text-sm">
                <thead>
                    <tr>
                        <th class="p-2">Staff ID</th>
                        <th>Period</th>
                        <th>Start</th>
                        <th>Calls</th>
                        <th>Follow-ups</th>
                        <th>Conversions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($targets as $t)
                        <tr class="border-t text-center">
                            <td class="p-2">{{ $t->user_id }}</td>
                            <td>{{ $t->period }}</td>
                            <td>{{ $t->period_start }}</td>
                            <td>{{ $t->calls_target }}</td>
                            <td>{{ $t->followups_target }}</td>
                            <td>{{ $t->conversions_target }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</x-layouts::app>
