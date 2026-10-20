<x-layouts::app title="Order Cancellation & Returns">
<div class="mx-auto max-w-4xl space-y-6 p-6">
    <h1 class="text-2xl font-bold">Cancellation / Return — {{ $order->order_number }}</h1>
    <a href="{{ route('growya.orders.show',$order) }}" class="text-emerald-600">← Back to order</a>
    @if(session('success'))<p class="rounded-lg bg-green-50 p-3 text-green-800">{{ session('success') }}</p>@endif
    @if($errors->any())<div class="rounded-lg bg-red-50 p-3 text-red-800">{{ $errors->first() }}</div>@endif
    <div class="rounded-xl border p-5">Status: <strong>{{ ucfirst($order->status) }}</strong> · Payment: <strong>{{ ucfirst($order->payment_status) }}</strong> · Total: ₹{{ number_format((float)$order->total_amount,2) }}</div>
    @php($canCancel=in_array($order->status,['pending','confirmed'],true))
    @php($canReturn=$order->status==='delivered')
    @if($canCancel || $canReturn)
      <form action="{{ route('growya.returns.submit',$order) }}" method="POST" class="space-y-4 rounded-xl border p-5">
        @csrf
        <h2 class="text-lg font-bold">Submit a Request</h2>
        <select name="type" required class="w-full rounded-lg border p-3">
          @if($canCancel)<option value="cancellation" @selected(old('type')==='cancellation')>Cancel order</option>@endif
          @if($canReturn)<option value="return" @selected(old('type')==='return')>Return delivered order</option>@endif
        </select>
        <textarea name="reason" required minlength="10" maxlength="2500" rows="4" class="w-full rounded-lg border p-3" placeholder="Reason for your request">{{ old('reason') }}</textarea>
        <button class="rounded-lg bg-emerald-700 px-5 py-3 font-semibold text-white">Submit Request</button>
        <p class="text-sm text-zinc-500">Submitting a request does not automatically issue a refund. The seller will review it.</p>
      </form>
    @endif
    <section class="space-y-3"><h2 class="text-xl font-bold">Request History</h2>
      @forelse($requests as $r)
        <article class="rounded-xl border p-5">
          <div class="flex justify-between gap-2"><strong>{{ ucfirst($r->type) }} #{{ $r->id }}</strong><span>{{ ucfirst($r->status) }}</span></div>
          <p class="mt-2 whitespace-pre-line">{{ $r->reason }}</p>
          @if($r->seller_note)<p class="mt-2 text-sm">Seller: {{ $r->seller_note }}</p>@endif
          @if($r->refund)<p class="mt-2 text-sm font-semibold text-green-700">Refund recorded: ₹{{ $r->refund->amount }} · {{ $r->refund->reference }}</p>@endif
          <p class="mt-2 text-xs text-zinc-500">{{ $r->created_at->format('d M Y h:i A') }}</p>
        </article>
      @empty<p class="text-zinc-500">No cancellation or return requests yet.</p>@endforelse
    </section>
</div>
</x-layouts::app>
