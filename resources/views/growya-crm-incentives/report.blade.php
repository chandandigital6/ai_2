<x-layouts::app :title="'Staff Targets & Incentive Leaderboard'">
    @php
        // Rank by approved incentive, then paid, then calls.
        $ranked = $reports->sortBy([['approved', 'desc'], ['paid', 'desc'], ['calls', 'desc']])->values();
        $money = fn($v) => '₹' . number_format((float) $v, 2);
        $lastMonth = now()->subMonthNoOverflow();
        $ranges = [
            'This month' => [now()->startOfMonth(), now()],
            'Last month' => [$lastMonth->copy()->startOfMonth(), $lastMonth->copy()->endOfMonth()],
            'Last 30 days' => [now()->subDays(29), now()],
        ];
    @endphp

    <div class="mx-auto max-w-7xl space-y-5 p-6">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h1 class="text-2xl font-bold">Targets & Incentive Leaderboard</h1>
                <p class="text-sm text-zinc-500">Calls and follow-ups are counted inside the date range; the converted
                    count is the current CRM snapshot.</p>
            </div>
            <a href="{{ route('growya.crm.incentives.index', $business) }}" class="rounded border px-4 py-2">Back to
                Incentives</a>
        </div>

        @if ($errors->any())
            <div class="rounded-lg bg-red-100 p-3 text-red-800">{{ $errors->first() }}</div>
        @endif

        <form method="GET" class="flex flex-wrap items-end gap-3">
            <label class="text-sm">From
                <input class="block rounded border p-2 dark:bg-zinc-800" type="date" name="from"
                    value="{{ $from->toDateString() }}" />
            </label>
            <label class="text-sm">To
                <input class="block rounded border p-2 dark:bg-zinc-800" type="date" name="to"
                    value="{{ $to->toDateString() }}" />
            </label>
            <button class="rounded bg-emerald-700 px-5 py-2 text-white">Apply</button>
            <div class="flex flex-wrap items-center gap-3 pb-2 text-sm">
                @foreach ($ranges as $label => [$start, $end])
                    <a class="text-emerald-700 underline dark:text-emerald-400"
                        href="{{ route('growya.crm.incentives.report', ['business' => $business->id, 'from' => $start->toDateString(), 'to' => $end->toDateString()]) }}">{{ $label }}</a>
                @endforeach
            </div>
        </form>

        <p class="text-sm text-amber-700">Targets shown below are existing staff targets whose period starts inside
            your selected range. A historic date-specific conversion rate cannot be reliably derived from the current
            CRM status alone.</p>

        <div class="overflow-x-auto rounded-xl border dark:border-zinc-700">
            <table class="w-full text-left text-sm">
                <thead>
                    <tr class="border-b bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900">
                        <th class="p-3">#</th>
                        <th class="p-3">Staff</th>
                        <th class="p-3">Calls</th>
                        <th class="p-3">Follow-ups</th>
                        <th class="p-3">Currently converted</th>
                        <th class="p-3">Targets</th>
                        <th class="p-3 text-right">Approved</th>
                        <th class="p-3 text-right">Paid</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($ranked as $r)
                        <tr class="border-b align-top dark:border-zinc-700">
                            <td class="p-3 font-semibold text-zinc-500">{{ $loop->iteration }}</td>
                            <td class="p-3 font-semibold">{{ $r->name }}</td>
                            <td class="p-3">{{ $r->calls }}</td>
                            <td class="p-3">{{ $r->followups }}</td>
                            <td class="p-3">{{ $r->conversions_current }}</td>
                            <td class="p-3">
                                @forelse($r->targets as $t)
                                    <div class="mb-1 text-xs">
                                        <span class="font-semibold">{{ ucfirst((string) $t->period) }}</span>
                                        · from
                                        {{ \Illuminate\Support\Carbon::parse($t->period_start)->format('d M Y') }}
                                        <div class="text-zinc-500">Calls {{ $t->calls_target }} · Follow-ups
                                            {{ $t->followups_target }} · Converted {{ $t->conversions_target }}</div>
                                    </div>
                                @empty
                                    <span class="text-zinc-400">—</span>
                                @endforelse
                            </td>
                            <td class="p-3 text-right">{{ $money($r->approved) }}</td>
                            <td class="p-3 text-right">{{ $money($r->paid) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="p-5 text-zinc-500">No active staff.</td>
                        </tr>
                    @endforelse
                </tbody>
                @if ($ranked->isNotEmpty())
                    <tfoot>
                        <tr class="bg-zinc-50 font-semibold dark:bg-zinc-900">
                            <td class="p-3" colspan="2">Total</td>
                            <td class="p-3">{{ $ranked->sum('calls') }}</td>
                            <td class="p-3">{{ $ranked->sum('followups') }}</td>
                            <td class="p-3">{{ $ranked->sum('conversions_current') }}</td>
                            <td class="p-3"></td>
                            <td class="p-3 text-right">{{ $money($ranked->sum('approved')) }}</td>
                            <td class="p-3 text-right">{{ $money($ranked->sum('paid')) }}</td>
                        </tr>
                    </tfoot>
                @endif
            </table>
        </div>
    </div>
</x-layouts::app>
