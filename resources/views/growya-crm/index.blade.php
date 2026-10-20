<x-layouts::app :title="'Business Customers'">
    <div class="mx-auto max-w-7xl space-y-5 px-5 py-8">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h1 class="text-2xl font-bold">{{ $business->name }} — Customer CRM</h1>
                <p class="text-sm text-zinc-500">Business-private contacts and follow-ups</p>
            </div>
            <div class="flex gap-2">
                <a class="rounded-lg bg-emerald-700 px-4 py-2 text-white"
                    href="{{ route('growya.crm.create', $business) }}">+ Add Customer</a>
                <a class="rounded-lg border px-4 py-2" href="{{ route('growya.crm.export', $business) }}">Export CSV</a>
            </div>
        </div>

        @if (session('success'))
            <p class="rounded-lg bg-emerald-100 p-3 text-emerald-900">{{ session('success') }}</p>
        @endif
        @if ($errors->any())
            <p class="rounded-lg bg-red-100 p-3 text-red-800">{{ $errors->first() }}</p>
        @endif

        <div class="grid grid-cols-2 gap-3 md:grid-cols-5">
            @foreach (['new', 'contacted', 'interested', 'converted', 'closed'] as $status)
                <div class="rounded-xl border p-4 dark:border-zinc-700">
                    <div class="text-xs uppercase text-zinc-500">{{ $status }}</div>
                    <div class="text-2xl font-bold">{{ $totals[$status] ?? 0 }}</div>
                </div>
            @endforeach
        </div>

        <form method="GET"
            class="grid items-center gap-3 rounded-xl border p-4 dark:border-zinc-700 sm:grid-cols-2 lg:grid-cols-5">
            <input class="rounded-lg border p-2 dark:bg-zinc-900" name="q" value="{{ request('q') }}"
                maxlength="120" placeholder="Name, email, phone, company">
            <select class="rounded-lg border p-2 dark:bg-zinc-900" name="status">
                <option value="">All statuses</option>
                @foreach (['new', 'contacted', 'interested', 'converted', 'closed'] as $s)
                    <option value="{{ $s }}" @selected(request('status') === $s)>{{ ucfirst($s) }}</option>
                @endforeach
            </select>
            <select name="source" class="rounded-lg border p-2 dark:bg-zinc-900">
                <option value="">All sources</option>
                @foreach (['manual', 'import', 'product_enquiry', 'service_enquiry', 'wishlist', 'chat', 'other'] as $s)
                    <option value="{{ $s }}" @selected(request('source') === $s)>
                        {{ ucfirst(str_replace('_', ' ', $s)) }}</option>
                @endforeach
            </select>
            <label class="flex items-center gap-2 text-sm">
                <input type="checkbox" name="overdue" value="1" @checked(request('overdue'))>
                Overdue open follow-ups
            </label>
            <div class="flex gap-2">
                <button class="flex-1 rounded-lg bg-emerald-700 p-2 text-white">Filter</button>
                @if (request()->hasAny(['q', 'status', 'source', 'overdue']))
                    <a href="{{ route('growya.crm.index', $business) }}"
                        class="rounded-lg border px-3 py-2 text-sm">Clear</a>
                @endif
            </div>
        </form>

        <div class="overflow-x-auto rounded-xl border dark:border-zinc-700">
            <table class="min-w-full text-left text-sm">
                <thead class="bg-zinc-100 dark:bg-zinc-800">
                    <tr>
                        <th class="p-3">Customer</th>
                        <th class="p-3">Phone / Email</th>
                        <th class="p-3">Status</th>
                        <th class="p-3">Source</th>
                        <th class="p-3">Follow-up</th>
                        <th class="p-3">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($contacts as $c)
                        @php
                            $overdue =
                                $c->next_follow_up_at &&
                                $c->next_follow_up_at->isPast() &&
                                !in_array($c->status, ['converted', 'closed'], true);
                        @endphp
                        <tr class="border-t dark:border-zinc-700">
                            <td class="p-3 font-semibold">
                                {{ $c->name }}
                                @if ($c->company)
                                    <div class="text-xs font-normal text-zinc-500">{{ $c->company }}</div>
                                @endif
                            </td>
                            <td class="p-3">
                                {{ $c->phone }}
                                <div class="text-zinc-500">{{ $c->email }}</div>
                            </td>
                            <td class="p-3">{{ ucfirst($c->status) }}</td>
                            <td class="p-3">{{ ucfirst(str_replace('_', ' ', $c->source)) }}</td>
                            <td class="p-3 {{ $overdue ? 'font-semibold text-red-600' : '' }}">
                                {{ $c->next_follow_up_at?->format('d M Y H:i') ?? '—' }}
                                @if ($overdue)
                                    <div class="text-xs">Overdue</div>
                                @endif
                            </td>
                            <td class="p-3">
                                <a class="text-emerald-600 underline"
                                    href="{{ route('growya.crm.show', [$business, $c->id]) }}">View</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="p-6 text-center">
                                {{ request()->hasAny(['q', 'status', 'source', 'overdue']) ? 'No customers match these filters.' : 'No customers yet.' }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{ $contacts->links() }}

        <form class="flex flex-wrap items-center gap-3 rounded-xl border p-4 dark:border-zinc-700" method="POST"
            enctype="multipart/form-data" action="{{ route('growya.crm.import', $business) }}">
            @csrf
            <label class="font-semibold">Import CRM CSV</label>
            <input type="file" name="file" accept=".csv,text/csv" required>
            <button class="rounded-lg bg-zinc-900 px-4 py-2 text-white dark:bg-zinc-600">Import (up to 2,000
                rows)</button>
            <span class="text-xs text-zinc-500">Use Export CSV for the column format. Only new customers are added;
                existing customers (same email) are skipped and never overwritten.</span>
        </form>
    </div>
</x-layouts::app>
