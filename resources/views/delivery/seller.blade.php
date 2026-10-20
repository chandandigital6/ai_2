<x-layouts::app title="Manage Delivery">
<div class="mx-auto max-w-4xl space-y-5 p-6"><h1 class="text-2xl font-bold">Delivery — {{ $order->order_number }}</h1>
@if(session('success'))<p class="rounded bg-green-100 p-3">{{session('success')}}</p>@endif
@if($errors->any())<p class="rounded bg-red-100 p-3">{{$errors->first()}}</p>@endif
<p>Customer: {{ $order->customer_name }} · Order Status: {{ $order->status }} · Tracking Stage: {{ str_replace('_',' ',$stage) }}</p>
@php($canEdit=app(\App\Services\BusinessAccessService::class)->can(auth()->user(),$business,'products.update'))
@php($next=match($stage){'confirmed'=>'packed','packed'=>'shipped','shipped'=>'out_for_delivery','out_for_delivery'=>'delivered',default=>null})
@if($canEdit && $next && in_array($order->status,['confirmed','shipped'],true))
<form method="POST" action="{{route('businesses.orders.delivery.update',[$business,$order])}}" class="rounded-xl border p-5 space-y-4">@csrf @method('PATCH')
<label class="block">Next Stage<select name="stage" class="mt-1 w-full rounded border p-3"><option value="{{ $next }}">{{ucwords(str_replace('_',' ',$next))}}</option>@if($stage==='shipped')<option value="delivered">Delivered (skip out for delivery)</option>@endif</select></label>
<label class="block">Courier Name<input name="courier_name" value="{{old('courier_name',$shipment?->courier_name)}}" maxlength="120" class="mt-1 w-full rounded border p-3"></label>
<label class="block">Tracking Number<input name="tracking_number" value="{{old('tracking_number',$shipment?->tracking_number)}}" maxlength="120" class="mt-1 w-full rounded border p-3"></label>
<label class="block">Tracking URL (HTTPS)<input name="tracking_url" type="url" value="{{old('tracking_url',$shipment?->tracking_url)}}" class="mt-1 w-full rounded border p-3"></label>
<label class="block">Estimated Delivery Date<input name="estimated_delivery_date" type="date" value="{{old('estimated_delivery_date',$shipment?->estimated_delivery_date?->format('Y-m-d'))}}" class="mt-1 w-full rounded border p-3"></label>
<label class="block">Update Note<textarea name="note" maxlength="2000" rows="3" class="mt-1 w-full rounded border p-3">{{old('note')}}</textarea></label>
<button class="rounded bg-emerald-700 px-5 py-3 font-semibold text-white">Update Delivery</button></form>
@else<p class="rounded border p-4 text-zinc-500">No next tracking stage is available for this order.</p>@endif
<div class="rounded-xl border p-5"><h2 class="mb-3 font-semibold">Tracking History</h2>@forelse($events as $event)<div class="border-b py-3"><b>{{ucwords(str_replace('_',' ',$event->stage))}}</b> — {{$event->created_at->format('d M Y h:i A')}}<p class="text-xs text-zinc-500">{{ $event->actor?->name ?? 'Staff' }}</p><p class="whitespace-pre-line">{{$event->note}}</p></div>@empty<p>No updates yet.</p>@endforelse</div>
<a class="text-emerald-700 underline" href="{{route('businesses.orders.show',[$business,$order])}}">Back to Order</a>
</div></x-layouts::app>
