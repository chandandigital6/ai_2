<x-layouts::app title="Order Tracking">
<div class="mx-auto max-w-4xl space-y-5 p-6">
<h1 class="text-2xl font-bold">Track Order #{{ $order->order_number }}</h1>
<p>Status: <strong>{{ ucfirst($order->status) }}</strong> · Payment: {{ ucfirst($order->payment_status) }}</p>
@if($order->status==='cancelled')<p class="rounded bg-rose-50 p-4 text-rose-700">This order is cancelled.</p>@endif
<div class="rounded-xl border p-5 space-y-3"><h2 class="font-semibold">Delivery Progress</h2>
@php($steps=['placed'=>'Placed','confirmed'=>'Confirmed','packed'=>'Packed','shipped'=>'Shipped','out_for_delivery'=>'Out for Delivery','delivered'=>'Delivered'])
@php($idx=array_search($stage,array_keys($steps),true))
@foreach($steps as $key=>$label)<div class="flex items-center gap-3"><span class="flex size-7 items-center justify-center rounded-full {{array_search($key,array_keys($steps),true)<=$idx?'bg-emerald-700 text-white':'bg-zinc-100 text-zinc-500'}}">{{array_search($key,array_keys($steps),true)+1}}</span><span>{{ $label }}</span></div>@endforeach
</div>
<div class="rounded-xl border p-5 space-y-2"><h2 class="font-semibold">Courier Details</h2>
<p>Courier: {{ $shipment?->courier_name ?: 'Not assigned' }}</p><p>Tracking Number: {{ $shipment?->tracking_number ?: 'Not assigned' }}</p>
<p>Estimated Delivery: {{ $shipment?->estimated_delivery_date?->format('d M Y') ?: 'Not available' }}</p>
@if($shipment?->tracking_url)<a class="text-emerald-700 underline" target="_blank" rel="noopener noreferrer" href="{{ $shipment->tracking_url }}">Track on courier website</a>@endif</div>
<div class="rounded-xl border p-5 space-y-4"><h2 class="font-semibold">Tracking History</h2>
<p class="text-sm text-zinc-500">Order placed: {{ $order->created_at?->format('d M Y h:i A') }}</p>
@forelse($events as $event)<div class="border-t pt-3"><p class="font-medium">{{ $steps[$event->stage]??ucwords(str_replace('_',' ',$event->stage)) }}</p><p class="text-sm text-zinc-500">{{ $event->created_at->format('d M Y h:i A') }}</p>@if($event->note)<p class="whitespace-pre-line text-sm">{{ $event->note }}</p>@endif</div>@empty<p class="text-sm text-zinc-500">No delivery updates yet.</p>@endforelse</div>
<a class="text-emerald-700 underline" href="{{ route('growya.orders.mine') }}">My Orders</a>
</div></x-layouts::app>
