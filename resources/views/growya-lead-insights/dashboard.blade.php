<x-layouts::app :title="$admin ? 'Platform Lead Insights' : 'Business Lead Insights'">
<div class="mx-auto max-w-7xl space-y-6 px-4 py-8 sm:px-6">
    <div>
        <h1 class="text-2xl font-bold">{{ $admin ? 'Platform Lead Insights' : 'Lead Insights — '.$business->name }}</h1>
        <p class="text-sm text-zinc-500">Enquiries, wishlist and chat interest. A converted status is manually recorded, not a verified sale.</p>
    </div>
    @php
        $route = $admin ? 'growya.lead-insights.admin' : 'growya.lead-insights.owner';
        $export = $admin ? 'growya.lead-insights.admin.export' : 'growya.lead-insights.owner.export';
        $params = $admin ? [] : ['business' => $business];
        $filters = request()->only(['from','to','source','status']);
    @endphp
    <form action="{{ route($route, $params) }}" method="GET" class="grid gap-3 rounded-2xl border bg-white p-4 dark:border-zinc-800 dark:bg-zinc-900 sm:grid-cols-5">
        <label class="text-sm">From<input type="date" name="from" value="{{ $from->toDateString() }}" class="mt-1 w-full rounded-lg border p-2 dark:bg-zinc-800"></label>
        <label class="text-sm">To<input type="date" name="to" value="{{ $to->toDateString() }}" class="mt-1 w-full rounded-lg border p-2 dark:bg-zinc-800"></label>
        <label class="text-sm">Source<select name="source" class="mt-1 w-full rounded-lg border p-2 dark:bg-zinc-800">
            @foreach(['all'=>'All','product_enquiry'=>'Product enquiry','service_enquiry'=>'Service enquiry','wishlist'=>'Wishlist','chat'=>'Chat'] as $key=>$name)
                <option value="{{ $key }}" @selected($source === $key)>{{ $name }}</option>
            @endforeach
        </select></label>
        <label class="text-sm">Status<select name="status" class="mt-1 w-full rounded-lg border p-2 dark:bg-zinc-800">
            @foreach(['all'=>'All','new'=>'New / Pending','contacted'=>'Contacted','follow_up'=>'Follow-up','converted'=>'Converted','closed'=>'Closed'] as $key=>$name)
                <option value="{{ $key }}" @selected($status === $key)>{{ $name }}</option>
            @endforeach
        </select></label>
        <button class="self-end rounded-lg bg-emerald-700 px-4 py-2.5 font-semibold text-white">Apply filters</button>
    </form>
    @if($errors->any())<p class="text-red-600">{{ $errors->first() }}</p>@endif
    <div class="flex justify-end"><a href="{{ route($export, array_merge($params, $filters)) }}" class="rounded-lg border px-4 py-2 text-sm font-semibold">Export filtered CSV</a></div>
    <div class="grid gap-4 sm:grid-cols-3">
        @foreach([['Total leads',$total],['Marked converted',$converted],['Overdue follow-ups',$overdue]] as $card)
            <div class="rounded-2xl border bg-white p-5 dark:border-zinc-800 dark:bg-zinc-900"><p class="text-sm text-zinc-500">{{ $card[0] }}</p><p class="text-3xl font-bold">{{ number_format($card[1]) }}</p></div>
        @endforeach
    </div>
    <p class="text-sm text-zinc-600 dark:text-zinc-400">Recorded conversion rate: <strong>{{ $total ? number_format($converted * 100 / $total, 1) : '0.0' }}%</strong>. A customer appearing in multiple sources is counted once per source record; this is not a deduplicated customer count.</p>
    <div class="grid gap-4 lg:grid-cols-2">
        <section class="rounded-2xl border p-5 dark:border-zinc-800"><h2 class="mb-3 font-semibold">Leads by source</h2>@forelse($bySource as $s)<div class="flex justify-between border-b py-2"><span>{{ str_replace('_',' ',ucfirst($s->source)) }}</span><strong>{{ $s->leads }}</strong></div>@empty<p>No records.</p>@endforelse</section>
        <section class="rounded-2xl border p-5 dark:border-zinc-800"><h2 class="mb-3 font-semibold">Recorded statuses</h2>@forelse($byStatus as $s)<div class="flex justify-between border-b py-2"><span>{{ $s->raw_status }}</span><strong>{{ $s->leads }}</strong></div>@empty<p>No records.</p>@endforelse</section>
        <section class="rounded-2xl border p-5 dark:border-zinc-800"><h2 class="mb-3 font-semibold">Top interested products</h2>@forelse($topProducts as $p)<div class="flex justify-between border-b py-2"><span>{{ $productNames[$p->product_id] ?? 'Product #'.$p->product_id }}</span><strong>{{ $p->interests }}</strong></div>@empty<p>No product interests.</p>@endforelse</section>
        <section class="rounded-2xl border p-5 dark:border-zinc-800"><h2 class="mb-3 font-semibold">Assigned staff workload</h2><p class="mb-2 text-xs text-zinc-500">Based only on records containing an assigned/updated-by user ID.</p>@forelse($staff as $s)<div class="flex justify-between border-b py-2"><span>{{ $staffNames[$s->assigned_user_id] ?? 'User #'.$s->assigned_user_id }}</span><span>{{ $s->handled }} handled / {{ $s->converted }} converted</span></div>@empty<p>No assigned records.</p>@endforelse</section>
    </div>
    @if($admin)
        <section class="rounded-2xl border p-5 dark:border-zinc-800"><h2 class="mb-3 font-semibold">Businesses with most lead records</h2>@forelse($byBusiness as $b)<div class="flex justify-between border-b py-2"><span>{{ $names[$b->business_id] ?? 'Business #'.$b->business_id }}</span><strong>{{ $b->leads }}</strong></div>@empty<p>No records.</p>@endforelse</section>
    @endif
    <section class="overflow-x-auto rounded-2xl border dark:border-zinc-800">
        <table class="min-w-full text-left text-sm"><thead class="bg-zinc-100 dark:bg-zinc-900"><tr><th class="p-3">Date</th><th class="p-3">Source</th><th class="p-3">Business</th><th class="p-3">Customer</th><th class="p-3">Status</th><th class="p-3">Assigned</th></tr></thead>
        <tbody>@forelse($rows as $row)<tr class="border-t dark:border-zinc-800"><td class="p-3">{{ $row->created_at }}</td><td class="p-3">{{ $row->source }} #{{ $row->source_id }}</td><td class="p-3">#{{ $row->business_id }}</td><td class="p-3">{{ $row->customer_name ?: ($row->customer_id ? 'User #'.$row->customer_id : 'Not linked') }}</td><td class="p-3">{{ $row->raw_status }}</td><td class="p-3">{{ $row->assigned_user_id ?: '—' }}</td></tr>@empty<tr><td colspan="6" class="p-6 text-center">No leads for selected filters.</td></tr>@endforelse</tbody></table>
    </section>
    {{ $rows->links() }}
</div>
</x-layouts::app>
