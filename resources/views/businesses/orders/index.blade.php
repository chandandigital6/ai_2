@php
    $statuses = ['pending', 'confirmed', 'shipped', 'delivered', 'cancelled'];

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
    $paymentLabel = ['cod' => 'COD', 'upi_manual' => 'Manual UPI'];
    $btn = 'inline-flex items-center rounded-xl border border-zinc-300 px-4 py-2 text-sm font-semibold hover:border-emerald-600 hover:text-emerald-700 dark:border-zinc-700';
@endphp

<x-layouts::app title="Business Orders">
    <div id="main-content" class="mx-auto max-w-6xl space-y-6 p-6">
        <div class="flex flex-wrap items-end justify-between gap-3">
            <div>
                <h1 class="text-3xl font-bold">Orders</h1>
                <p class="text-sm text-zinc-500">{{ $business->name }}</p>
            </div>

            <div class="flex flex-wrap gap-2">
                <a href="{{ route('businesses.payments.index', $business) }}" wire:navigate class="{{ $btn }}">Verify UPI Payments</a>
                <a href="{{ route('businesses.returns.index', $business) }}" wire:navigate class="{{ $btn }}">Returns &amp; Refunds</a>
                <a href="{{ route('businesses.delivery.index', $business) }}" wire:navigate class="{{ $btn }}">Delivery Management</a>
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

        {{-- Filters --}}
        <form method="GET" class="flex flex-wrap items-center gap-3">
            <input name="search" value="{{ request('search') }}" placeholder="Order ID / customer / phone"
                aria-label="Search orders"
                class="min-w-[16rem] flex-1 rounded-lg border p-2.5 dark:border-zinc-700 dark:bg-zinc-900">

            <select name="status" aria-label="Filter by status"
                class="rounded-lg border p-2.5 dark:border-zinc-700 dark:bg-zinc-900">
                <option value="">All statuses</option>
                @foreach ($statuses as $s)
                    <option value="{{ $s }}" @selected(request('status') === $s)>{{ ucfirst($s) }}</option>
                @endforeach
            </select>

            <button type="submit" class="rounded-lg bg-emerald-700 px-5 py-2.5 font-semibold text-white hover:bg-emerald-800">Filter</button>

            @if (request()->filled('search') || request()->filled('status'))
                <a href="{{ route('businesses.orders.index', $business) }}" class="text-sm underline">Reset</a>
            @endif
        </form>

        {{-- Orders --}}
        <div class="space-y-3">
            @forelse ($orders as $order)
                <a href="{{ route('businesses.orders.show', [$business, $order]) }}"
                    class="block rounded-2xl border bg-white p-4 shadow-sm transition hover:border-emerald-600 dark:border-zinc-700 dark:bg-zinc-900">
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div class="min-w-0">
                            <p class="font-bold">{{ $order->order_number }}</p>
                            <p class="text-sm text-zinc-500">
                                {{ $order->customer_name }} · {{ $order->customer_phone }}
                            </p>
                            <p class="mt-1 text-xs text-zinc-400">
                                {{ $order->created_at?->format('d M Y, h:i A') }}
                                · {{ $paymentLabel[$order->payment_method] ?? strtoupper($order->payment_method) }}
                            </p>
                        </div>

                        <div class="space-y-2 text-right">
                            <p class="text-lg font-bold">₹{{ number_format((float) $order->total_amount, 2) }}</p>
                            <div class="flex flex-wrap justify-end gap-2 text-xs font-semibold">
                                <span class="rounded-full px-2.5 py-1 {{ $statusBadge[$order->status] ?? $fallbackBadge }}">
                                    {{ ucfirst($order->status) }}
                                </span>
                                <span class="rounded-full px-2.5 py-1 {{ $paymentBadge[$order->payment_status] ?? $fallbackBadge }}">
                                    Payment: {{ ucfirst($order->payment_status) }}
                                </span>
                            </div>
                        </div>
                    </div>
                </a>
            @empty
                <div class="rounded-2xl border border-dashed p-12 text-center dark:border-zinc-700">
                    <p class="font-semibold">No orders found.</p>
                    @if (request()->filled('search') || request()->filled('status'))
                        <p class="mt-1 text-sm text-zinc-500">Filter badal kar dobara try karo.</p>
                    @endif
                </div>
            @endforelse
        </div>

        {{ $orders->links() }}
    </div>
</x-layouts::app>