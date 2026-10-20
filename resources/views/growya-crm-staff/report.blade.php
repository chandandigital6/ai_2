<x-layouts::app :title="'Staff CRM Performance'">
    <div class="mx-auto max-w-6xl px-5 py-8 space-y-5">
        <h1 class="text-2xl font-bold">Staff CRM Performance — {{ $business->name }}</h1>
        <a class="text-emerald-700 underline" href="{{ route('growya.crm.staff.dashboard', $business) }}">← Dashboard</a>
        <form method="GET" class="flex flex-wrap items-end gap-3"><label>From<input class="block rounded-lg border p-2"
                    type="date" name="from" value="{{ $from->toDateString() }}"></label><label>To<input
                    class="block rounded-lg border p-2" type="date" name="to"
                    value="{{ $to->toDateString() }}"></label><button
                class="rounded-lg bg-emerald-700 p-2 text-white">Filter</button></form>
        <p class="text-sm text-slate-500">Calls, follow-ups and meetings use activity timestamps in the selected dates.
            Assigned and currently converted leads are current snapshots, not historical conversion events or verified
            sales.</p>
        <div class="overflow-x-auto rounded-xl border">
            <table class="w-full text-sm text-left">
                <thead class="border-b">
                    <tr>
                        <th class="p-3">Staff</th>
                        <th class="p-3">Assigned Now</th>
                        <th class="p-3">Converted Now</th>
                        <th class="p-3">Calls Logged</th>
                        <th class="p-3">Follow-ups Logged</th>
                        <th class="p-3">Meetings Logged</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($reports as $r)
                        <tr class="border-b">
                            <td class="p-3">{{ $r->name }}</td>
                            <td class="p-3">{{ $r->assigned }}</td>
                            <td class="p-3">{{ $r->converted_current }}</td>
                            <td class="p-3">{{ $r->calls }}</td>
                            <td class="p-3">{{ $r->followups }}</td>
                            <td class="p-3">{{ $r->meetings }}</td>
                    </tr>@empty<tr>
                            <td colspan="6" class="p-4">No active staff.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-layouts::app>
