@php
    $badge = [
        'pending' => 'bg-amber-50 text-amber-700 dark:bg-amber-950 dark:text-amber-300',
        'approved' => 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300',
        'rejected' => 'bg-red-50 text-red-700 dark:bg-red-950 dark:text-red-300',
        'paid' => 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300',
        'failed' => 'bg-red-50 text-red-700 dark:bg-red-950 dark:text-red-300',
    ];
    $fallbackBadge = 'bg-zinc-100 text-zinc-600 dark:bg-zinc-800 dark:text-zinc-300';
    $paymentLabel = ['cod' => 'Cash on Delivery', 'upi_manual' => 'Manual UPI'];

    $hasPending = $submissions->contains('status', 'pending');
    $canSubmit = $order->payment_method === 'upi_manual'
        && $order->payment_status === 'pending'
        && !in_array($order->status, ['cancelled', 'delivered'], true)
        && !$hasPending;

    $inputClass = 'mt-2 block w-full rounded-xl border border-zinc-300 bg-white p-3 text-zinc-900 focus:border-emerald-600 focus:outline-none focus:ring-2 focus:ring-emerald-100 dark:border-zinc-700';
@endphp

<x-layouts::app title="UPI Payment Verification">
    <div id="main-content" class="mx-auto max-w-4xl space-y-6 p-4 md:p-6">
        <a href="{{ route('growya.orders.show', $order) }}" wire:navigate
            class="text-sm font-semibold text-emerald-700 underline dark:text-emerald-400">← Back to order</a>

        <div>
            <h1 class="text-2xl font-bold md:text-3xl">UPI Payment</h1>
            <p class="text-sm text-zinc-500">Order {{ $order->order_number }}</p>
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

        {{-- Summary --}}
        <section class="space-y-4 rounded-2xl border bg-white p-5 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
            <div class="flex flex-wrap items-end justify-between gap-3">
                <div>
                    <p class="text-sm text-zinc-500">Payable amount</p>
                    <p class="text-3xl font-black">₹{{ number_format((float) $order->total_amount, 2) }}</p>
                </div>
                <div class="flex flex-wrap gap-2 text-xs font-semibold">
                    <span class="rounded-full px-3 py-1.5 {{ $fallbackBadge }}">
                        {{ $paymentLabel[$order->payment_method] ?? strtoupper($order->payment_method ?? 'N/A') }}
                    </span>
                    <span class="rounded-full px-3 py-1.5 {{ $badge[$order->payment_status] ?? $fallbackBadge }}">
                        Payment: {{ ucfirst($order->payment_status) }}
                    </span>
                </div>
            </div>

            <p class="rounded-lg bg-amber-50 p-3 text-sm text-amber-800 dark:bg-amber-950 dark:text-amber-200">
                Seller ke verified UPI details par <strong>exact amount</strong> transfer karne ke baad hi proof submit karo.
                Proof submit karna payment confirm hona nahi hai. Seller verify karega.
            </p>
        </section>

        {{-- State messages --}}
        @if ($hasPending)
            <div class="rounded-2xl border border-amber-300 bg-amber-50 p-4 text-sm text-amber-900 dark:border-amber-800 dark:bg-amber-950 dark:text-amber-200">
                Aapka payment proof seller ke verification ke liye pending hai. Verify hone tak naya proof submit nahi ho sakta.
            </div>
        @elseif ($order->payment_status === 'paid')
            <div class="rounded-2xl border border-emerald-300 bg-emerald-50 p-4 text-sm font-semibold text-emerald-900 dark:border-emerald-800 dark:bg-emerald-950 dark:text-emerald-200">
                Payment verify ho chuka hai. Dhanyavaad!
            </div>
        @endif

        {{-- Submit form --}}
        @if ($canSubmit)
            <form action="{{ route('growya.payments.submit', $order) }}" method="POST" enctype="multipart/form-data"
                class="space-y-5 rounded-2xl border bg-white p-5 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
                @csrf
                <h2 class="text-lg font-semibold">Submit UPI proof</h2>

                <div>
                    <label for="utr" class="block text-sm font-medium">UPI Transaction / UTR ID <span class="text-red-600">*</span></label>
                    <input id="utr" name="utr" value="{{ old('utr') }}" required maxlength="100" autocomplete="off"
                        class="{{ $inputClass }}" placeholder="Enter transaction reference">
                    @error('utr') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="proof" class="block text-sm font-medium">
                        Payment screenshot / PDF <span class="font-normal text-zinc-500">(optional, max 5 MB)</span>
                    </label>
                    <input id="proof" type="file" name="proof" accept="image/jpeg,image/png,image/webp,application/pdf"
                        class="{{ $inputClass }} file:mr-3 file:rounded-lg file:border-0 file:bg-emerald-50 file:px-3 file:py-2 file:font-semibold file:text-emerald-700">
                    @error('proof') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                <button type="submit"
                    class="w-full rounded-xl bg-emerald-700 px-5 py-3 font-semibold text-white transition hover:bg-emerald-800 sm:w-auto">
                    Submit for seller verification
                </button>
            </form>
        @endif

        {{-- Attempts --}}
        <section class="rounded-2xl border bg-white p-5 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
            <h2 class="mb-4 text-lg font-semibold">Payment attempts</h2>

            <div class="space-y-3">
                @forelse ($submissions as $item)
                    <div class="space-y-2 rounded-xl border p-4 dark:border-zinc-700">
                        <div class="flex flex-wrap items-start justify-between gap-2">
                            <div>
                                <p class="font-mono font-semibold">{{ $item->utr }}</p>
                                <p class="text-xs text-zinc-400">{{ $item->created_at?->format('d M Y, h:i A') }}</p>
                            </div>
                            <span class="rounded-full px-3 py-1 text-xs font-semibold {{ $badge[$item->status] ?? $fallbackBadge }}">
                                {{ ucfirst($item->status) }}
                            </span>
                        </div>

                        @if ($item->rejection_reason)
                            <p class="rounded-lg bg-red-50 p-3 text-sm text-red-700 dark:bg-red-950 dark:text-red-300">
                                <strong>Reason:</strong> {{ $item->rejection_reason }}
                            </p>
                        @endif

                        @if ($item->proof_path)
                            <a class="inline-flex rounded-lg border px-3 py-1.5 text-sm font-semibold text-emerald-700 hover:border-emerald-600 dark:border-zinc-700 dark:text-emerald-400"
                                href="{{ route('growya.payments.customer-proof', [$order, $item]) }}">⬇ Download your proof</a>
                        @endif
                    </div>
                @empty
                    <p class="text-sm text-zinc-500">No payment proof submitted yet.</p>
                @endforelse
            </div>
        </section>
    </div>
</x-layouts::app>