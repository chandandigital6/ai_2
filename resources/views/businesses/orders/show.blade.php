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

    // Progress steps
    $steps = ['pending', 'confirmed', 'shipped', 'delivered'];
    $currentStep = array_search($order->status, $steps, true);
    $isCancelled = $order->status === 'cancelled';

    // Address
    $addr = (array) $order->address_snapshot;
    $addressLine = collect([$addr['address'] ?? null, $addr['city'] ?? null, $addr['state'] ?? null])
        ->filter()
        ->implode(', ');
    $pincode = $addr['pincode'] ?? null;

    // Actions
    $canEdit = app(\App\Services\BusinessAccessService::class)->can(auth()->user(), $business, 'products.update');
    $next = match ($order->status) {
        'pending' => 'confirmed',
        'confirmed' => 'shipped',
        'shipped' => 'delivered',
        default => null,
    };
    $canCancel = in_array($order->status, ['pending', 'confirmed'], true) && $order->payment_status !== 'paid';
    $showActions = $canEdit && in_array($order->status, ['pending', 'confirmed', 'shipped'], true);
@endphp

<x-layouts::app title="Manage Order">
    <div id="main-content" class="mx-auto max-w-5xl space-y-6 p-6">
        <a href="{{ route('businesses.orders.index', $business) }}" wire:navigate
            class="text-sm font-semibold text-emerald-700 underline dark:text-emerald-400">← All Orders</a>

        {{-- Header --}}
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <h1 class="text-3xl font-bold">Order {{ $order->order_number }}</h1>
                <p class="mt-1 text-sm text-zinc-500">
                    Placed {{ $order->created_at?->format('d M Y, h:i A') }} · {{ $business->name }}
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
                Ye order cancel ho chuka hai. Stock wapas add kar diya gaya hai.
            </div>
        @else
            <ol class="grid grid-cols-4 gap-2 text-center text-xs font-semibold" aria-label="Order progress">
                @foreach ($steps as $i => $step)
                    @php $done = $currentStep !== false && $i <= $currentStep; @endphp
                    <li>
                        <div class="h-2 rounded-full {{ $done ? 'bg-emerald-600' : 'bg-zinc-200 dark:bg-zinc-700' }}"></div>
                        <span class="mt-2 block {{ $done ? 'text-emerald-700 dark:text-emerald-400' : 'text-zinc-400' }}">
                            {{ ucfirst($step) }}
                        </span>
                    </li>
                @endforeach
            </ol>
        @endif

        <div class="grid gap-6 lg:grid-cols-3">
            <div class="space-y-6 lg:col-span-2">
                {{-- Items --}}
                <section class="overflow-hidden rounded-2xl border bg-white shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
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
                                    @if ($item->sku)
                                        <p class="text-xs text-zinc-400">SKU: {{ $item->sku }}</p>
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

                {{-- Actions --}}
                @if ($showActions)
                    <section class="space-y-3 rounded-2xl border bg-white p-5 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
                        <h2 class="font-bold">Update Order</h2>

                        <div class="flex flex-wrap gap-3">
                            @if ($next)
                                <form method="POST" action="{{ route('businesses.orders.status', [$business, $order]) }}">
                                    @csrf @method('PATCH')
                                    <input type="hidden" name="status" value="{{ $next }}">
                                    <button type="submit"
                                        class="rounded-xl bg-emerald-700 px-5 py-2.5 font-semibold text-white hover:bg-emerald-800">
                                        Mark as {{ ucfirst($next) }}
                                    </button>
                                </form>
                            @endif

                            @if ($canCancel)
                                <form method="POST" action="{{ route('businesses.orders.status', [$business, $order]) }}"
                                    onsubmit="return confirm('Cancel order and restore stock?')">
                                    @csrf @method('PATCH')
                                    <input type="hidden" name="status" value="cancelled">
                                    <button type="submit"
                                        class="rounded-xl border border-red-600 px-5 py-2.5 font-semibold text-red-600 hover:bg-red-50 dark:hover:bg-red-950">
                                        Cancel Order
                                    </button>
                                </form>
                            @endif
                        </div>

                        @if (!$canCancel && $order->payment_status === 'paid' && in_array($order->status, ['pending', 'confirmed'], true))
                            <p class="text-xs text-amber-700 dark:text-amber-400">
                                Paid order cancel karne se pehle refund karna hoga.
                            </p>
                        @endif
                    </section>
                @endif
            </div>

            {{-- Side --}}
            <aside class="space-y-6">
                <section class="space-y-2 rounded-2xl border bg-white p-5 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
                    <h2 class="font-bold">Customer</h2>
                    <p>{{ $order->customer_name }}</p>
                    <p class="text-sm">
                        <a class="text-emerald-700 underline dark:text-emerald-400"
                            href="tel:{{ preg_replace('/[^0-9+]/', '', $order->customer_phone) }}">{{ $order->customer_phone }}</a>
                    </p>
                </section>

                <section class="space-y-2 rounded-2xl border bg-white p-5 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
                    <h2 class="font-bold">Delivery Address</h2>
                    <p class="text-sm leading-6 text-zinc-600 dark:text-zinc-300">
                        {{ $addressLine ?: 'Address not available' }}
                        @if ($pincode)
                            <br>PIN: {{ $pincode }}
                        @endif
                    </p>
                </section>

                <section class="space-y-2 rounded-2xl border bg-white p-5 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
                    <h2 class="font-bold">Payment</h2>
                    <p class="text-sm">{{ $paymentLabel[$order->payment_method] ?? strtoupper($order->payment_method) }}</p>

                    @if ($order->notes)
                        <div class="border-t pt-3 dark:border-zinc-700">
                            <p class="text-xs font-semibold uppercase tracking-wide text-zinc-400">Customer note</p>
                            <p class="mt-1 whitespace-pre-line text-sm text-zinc-600 dark:text-zinc-300">{{ $order->notes }}</p>
                        </div>
                    @endif
                </section>

                <a href="{{ route('businesses.orders.delivery.show', [$business, $order]) }}" wire:navigate
                    class="block rounded-xl bg-emerald-700 px-5 py-3 text-center font-semibold text-white hover:bg-emerald-800">
                    Manage Delivery
                </a>
            </aside>
        </div>
    </div>
</x-layouts::app>