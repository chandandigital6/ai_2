@php $business = $order->business; @endphp
@php
    $statusBadge = [
        'pending' => 'bg-amber-50 text-amber-700 dark:bg-amber-950 dark:text-amber-300',
        'confirmed' => 'bg-blue-50 text-blue-700 dark:bg-blue-950 dark:text-blue-300',
        'shipped' => 'bg-indigo-50 text-indigo-700 dark:bg-indigo-950 dark:text-indigo-300',
        'delivered' => 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300',
        'cancelled' => 'bg-red-50 text-red-700 dark:bg-red-950 dark:text-red-300',
    ];

    $paymentBadge = [
        'pending' => 'bg-amber-50 text-amber-700 dark:bg-amber-950 dark:text-amber-300',
        'paid' => 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300',
        'failed' => 'bg-red-50 text-red-700 dark:bg-red-950 dark:text-red-300',
    ];

    $fallbackBadge = 'bg-zinc-100 text-zinc-600 dark:bg-zinc-800 dark:text-zinc-300';
    $paymentLabel = ['cod' => 'Cash on Delivery', 'upi_manual' => 'Manual UPI'];

    $steps = ['pending', 'confirmed', 'shipped', 'delivered'];
    $currentStep = array_search($order->status, $steps, true);
    $isCancelled = $order->status === 'cancelled';

    $addr = (array) $order->address_snapshot;
    $addressLine = collect([$addr['address'] ?? null, $addr['city'] ?? null, $addr['state'] ?? null])->filter()->implode(', ');
    $pincode = $addr['pincode'] ?? null;

    $needsUpi = strtolower((string) $order->payment_method) === 'upi_manual'
        && strtolower((string) $order->payment_status) !== 'paid'
        && !$isCancelled;

    $primaryBtn = 'inline-flex items-center justify-center rounded-xl bg-emerald-700 px-5 py-3 font-semibold text-white transition hover:bg-emerald-800';
    $outlineBtn = 'inline-flex items-center justify-center rounded-xl border border-emerald-700 px-5 py-3 font-semibold text-emerald-700 transition hover:bg-emerald-50 dark:hover:bg-emerald-950';
@endphp

@extends('public.businesses.layout')
@section('content')
@include('checkout.public-style')
<div class="g-container checkout-public">
    <div data-checkout-content class="mx-auto max-w-5xl space-y-6 p-4 md:p-6">
        <a href="{{ route('growya.orders.mine') }}" wire:navigate
            class="text-sm font-semibold text-emerald-700 underline dark:text-emerald-400">← My Orders</a>

        {{-- Header --}}
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold md:text-3xl">Order {{ $order->order_number }}</h1>
                <p class="mt-1 text-sm text-zinc-500">
                    Placed {{ $order->created_at?->format('d M Y, h:i A') }}
                    @if ($order->business) · Seller: <strong>{{ $order->business->name }}</strong> @endif
                </p>
            </div>

            <div class="flex flex-wrap gap-2 text-xs font-semibold">
                <span class="rounded-full px-3 py-1.5 {{ $statusBadge[$order->status] ?? $fallbackBadge }}">
                    Order: {{ ucfirst($order->status) }}
                </span>
                <span class="rounded-full px-3 py-1.5 {{ $paymentBadge[$order->payment_status] ?? $fallbackBadge }}">
                    Payment: {{ ucfirst($order->payment_status) }}
                </span>
            </div>
        </div>

        @if (session('success'))
            <div class="rounded-xl bg-emerald-50 p-4 text-sm font-semibold text-emerald-800 dark:bg-emerald-950 dark:text-emerald-200" role="status">
                {{ session('success') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="rounded-xl bg-red-50 p-4 text-sm text-red-800 dark:bg-red-950 dark:text-red-200" role="alert">
                {{ $errors->first() }}
            </div>
        @endif

        {{-- Progress --}}
        @if ($isCancelled)
            <div class="rounded-2xl bg-red-50 p-4 text-sm font-semibold text-red-800 dark:bg-red-950 dark:text-red-200">
                Ye order cancel ho chuka hai.
            </div>
        @else
            <ol class="grid grid-cols-4 gap-2 text-center text-xs font-semibold" aria-label="Order progress">
                @foreach ($steps as $i => $step)
                    @php $done = $currentStep !== false && $i <= $currentStep; @endphp
                    <li>
                        <div class="h-2 rounded-full {{ $done ? 'bg-emerald-600' : 'bg-zinc-200 dark:bg-zinc-700' }}"></div>
                        <span class="mt-2 block {{ $done ? 'text-emerald-700 dark:text-emerald-400' : 'text-zinc-400' }}">{{ ucfirst($step) }}</span>
                    </li>
                @endforeach
            </ol>
        @endif

        {{-- UPI call-to-action --}}
        @if ($needsUpi)
            <section class="rounded-2xl border border-emerald-200 bg-emerald-50 p-5 dark:border-emerald-800 dark:bg-emerald-950">
                <h2 class="text-lg font-bold text-emerald-900 dark:text-emerald-200">UPI Payment Verification</h2>
                <p class="mt-2 text-sm text-emerald-800 dark:text-emerald-300">
                    Payment करने के बाद अपना UTR / Transaction ID और Payment Screenshot Submit करें।
                </p>
                <a href="{{ route('growya.payments.customer', $order) }}" class="{{ $primaryBtn }} mt-4">
                    Submit UTR &amp; Payment Proof
                </a>
            </section>
        @endif

        <div class="grid gap-6 lg:grid-cols-3">
            {{-- Items --}}
            <section class="overflow-hidden rounded-2xl border bg-white shadow-sm dark:border-zinc-700 dark:bg-zinc-900 lg:col-span-2">
                <h2 class="border-b p-4 font-bold dark:border-zinc-700">Items ({{ $order->items->count() }})</h2>

                <div class="divide-y dark:divide-zinc-700">
                    @foreach ($order->items as $item)
                        @php $variantName = data_get($item->product_snapshot, 'variant_name'); @endphp
                        <div class="flex flex-wrap items-start justify-between gap-3 p-4">
                            <div class="min-w-0">
                                <p class="font-semibold">{{ $item->product_name }}</p>
                                @if ($variantName)
                                    <p class="text-sm text-zinc-500">{{ $variantName }}</p>
                                @endif
                                <p class="mt-1 text-sm text-zinc-500">
                                    ₹{{ number_format((float) $item->unit_price, 2) }} × {{ $item->quantity }}
                                </p>
                            </div>
                            <p class="font-bold">₹{{ number_format((float) $item->total_price, 2) }}</p>
                        </div>
                    @endforeach
                </div>

                <dl class="space-y-2 border-t bg-zinc-50 p-4 text-sm dark:border-zinc-700 dark:bg-zinc-800">
                    <div class="flex justify-between">
                        <dt class="text-zinc-500">Subtotal</dt>
                        <dd>₹{{ number_format((float) $order->subtotal, 2) }}</dd>
                    </div>

                    @if ((float) $order->discount_amount > 0)
                        <div class="flex justify-between text-emerald-700 dark:text-emerald-400">
                            <dt>Coupon discount</dt>
                            <dd>− ₹{{ number_format((float) $order->discount_amount, 2) }}</dd>
                        </div>
                    @endif

                    @if ((float) $order->tax_amount > 0)
                        <div class="flex justify-between">
                            <dt class="text-zinc-500">Tax</dt>
                            <dd>₹{{ number_format((float) $order->tax_amount, 2) }}</dd>
                        </div>
                    @endif

                    <div class="flex justify-between border-t pt-2 text-lg font-bold dark:border-zinc-700">
                        <dt>Total</dt>
                        <dd>₹{{ number_format((float) $order->total_amount, 2) }}</dd>
                    </div>
                </dl>
            </section>

            {{-- Side --}}
            <aside class="space-y-6">
                <section class="space-y-2 rounded-2xl border bg-white p-5 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
                    <h2 class="font-bold">Delivery Address</h2>
                    <p class="text-sm">{{ $order->customer_name }} · {{ $order->customer_phone }}</p>
                    <p class="text-sm leading-6 text-zinc-600 dark:text-zinc-300">
                        {{ $addressLine ?: 'Address not available' }}
                        @if ($pincode) <br>PIN: {{ $pincode }} @endif
                    </p>
                </section>

                <section class="space-y-1 rounded-2xl border bg-white p-5 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
                    <h2 class="font-bold">Payment</h2>
                    <p class="text-sm">{{ $paymentLabel[$order->payment_method] ?? strtoupper((string) $order->payment_method) }}</p>
                </section>
            </aside>
        </div>

        {{-- Actions --}}
        <div class="flex flex-wrap gap-3">
            <a href="{{ route('growya.orders.tracking', $order) }}" class="{{ $primaryBtn }}">Track My Order</a>

            @unless ($isCancelled)
                <a href="{{ route('growya.returns.customer', $order) }}" class="{{ $outlineBtn }}">
                    Cancellation / Return Request
                </a>
            @endunless
        </div>
    </div>
@if($order->payment_method==='razorpay' && $order->payment_status==='pending' && in_array($order->status,['pending','confirmed']))
<section class="mx-auto max-w-5xl p-6"><button type="button" id="pay-online" class="g-btn g-btn-primary">Pay ₹{{ number_format((float)$order->total_amount,2) }} online</button><p id="payment-message" role="status"></p></section>
<script src="https://checkout.razorpay.com/v1/checkout.js"></script><script>document.getElementById('pay-online').onclick=async function(){const btn=this,out=document.getElementById('payment-message');btn.disabled=true;const headers={'Content-Type':'application/json','Accept':'application/json','X-CSRF-TOKEN':@json(csrf_token())};try{const res=await fetch(@json(route('extensions.gateway.create',$order)),{method:'POST',headers,body:JSON.stringify({request_key:'web-order-{{ $order->id }}'})});const body=await res.json();if(!res.ok)throw Error(body.message||'Unable to create payment');const d=body.data;if(!d.provider_order_id)throw Error('Payment creation needs reconciliation. Please contact support.');const checkout=new Razorpay({key:d.key_id,order_id:d.provider_order_id,amount:d.amount_paise,currency:d.currency,name:@json($order->business?->name??'Growya'),handler:async response=>{try{const verified=await fetch(@json(route('extensions.gateway.verify',$order)),{method:'POST',headers,body:JSON.stringify(response)});const result=await verified.json();if(!verified.ok||!result.data.paid)throw Error(result.message||'Payment needs review. Please contact support.');location.reload();}catch(err){out.textContent=err.message}},modal:{ondismiss:()=>{btn.disabled=false}}});checkout.on('payment.failed',()=>{out.textContent='Payment did not complete. You can retry the same payment order.';btn.disabled=false});checkout.open();}catch(err){out.textContent=err.message;btn.disabled=false}};</script>
@endif
</div>
@endsection