<x-layouts::app title="Order Returns & Refunds">
<div class="mx-auto max-w-6xl space-y-5 p-6">
<h1 class="text-2xl font-bold">Returns & Refunds — {{ $business->name }}</h1>
<a href="{{ route('businesses.orders.index',$business) }}" class="text-emerald-600">← Back to orders</a>
@if(session('success'))<p class="rounded-lg bg-green-50 p-3 text-green-800">{{ session('success') }}</p>@endif
@if($errors->any())<p class="rounded-lg bg-red-50 p-3 text-red-800">{{ $errors->first() }}</p>@endif
<form method="GET" class="flex gap-3"><select name="status" class="rounded-lg border p-2"><option value="">All requests</option>@foreach(['pending','approved','rejected','refunded','resolved'] as $s)<option value="{{ $s }}" @selected(request('status')===$s)>{{ ucfirst($s) }}</option>@endforeach</select><button class="rounded-lg bg-zinc-900 px-4 py-2 text-white">Filter</button></form>
@forelse($requests as $r)
<article class="space-y-4 rounded-xl border p-5">
<div class="flex flex-wrap justify-between gap-2"><div><strong>{{ ucfirst($r->type) }} #{{ $r->id }}</strong> · {{ $r->order?->order_number }} · {{ $r->order?->customer_name }}</div><span class="font-semibold">{{ ucfirst($r->status) }}</span></div>
<div class="text-sm">Order: {{ $r->order?->status }} · Payment: {{ $r->order?->payment_status }} · Total: ₹{{ number_format((float)($r->order?->total_amount ?? 0),2) }}</div>
<p class="whitespace-pre-line">{{ $r->reason }}</p>
@if($r->seller_note)<p class="text-sm">Seller response: {{ $r->seller_note }}</p>@endif
@if($r->refund)<p class="rounded-lg bg-green-50 p-3 text-sm text-green-800">Refund record: ₹{{ $r->refund->amount }} · {{ $r->refund->method }} · Ref: {{ $r->refund->reference }}</p>@endif
@if($r->status==='pending')
<form method="POST" action="{{ route('businesses.returns.review',[$business,$r]) }}" class="space-y-3">@csrf @method('PATCH')<textarea name="seller_note" maxlength="2000" rows="2" class="w-full rounded-lg border p-3" placeholder="Seller note / reason for rejection"></textarea><div class="flex gap-3"><button name="decision" value="approve" class="rounded-lg bg-emerald-700 px-4 py-2 text-white">Approve</button><button name="decision" value="reject" class="rounded-lg bg-red-700 px-4 py-2 text-white">Reject (reason required)</button></div></form>
@elseif($r->status==='approved' && $r->order?->payment_status==='paid')
<div class="rounded-xl border border-amber-300 p-4"><p class="mb-3 font-semibold">After completing the FULL refund outside Growya, record its reference.</p><form method="POST" action="{{ route('businesses.returns.refund',[$business,$r]) }}" class="grid gap-3 md:grid-cols-2">@csrf @method('PATCH')<select name="method" required class="rounded-lg border p-3"><option value="manual_upi">UPI</option><option value="bank_transfer">Bank Transfer</option><option value="other">Other</option></select><input name="reference" required minlength="6" maxlength="120" class="rounded-lg border p-3" placeholder="Refund transaction reference"><textarea name="note" maxlength="1000" class="rounded-lg border p-3 md:col-span-2" placeholder="Optional note"></textarea><button type="submit" onclick="return confirm('Have you ALREADY sent the full refund to the customer?')" class="rounded-lg bg-emerald-700 px-4 py-3 font-semibold text-white md:col-span-2">Record Full Refund Completed</button></form></div>
@elseif($r->status==='approved')<p class="rounded-lg bg-amber-50 p-3 text-sm">This order is not marked Paid. Resolve any COD/cash settlement manually before recording a refund. Delivered returns require manual inventory inspection and adjustment.</p>@endif
</article>
@empty<p class="rounded-xl border p-6 text-zinc-500">No requests found.</p>@endforelse
{{ $requests->links() }}
</div></x-layouts::app>
