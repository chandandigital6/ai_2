@php
    $statuses = ['pending', 'approved', 'rejected'];

    $badge = [
        'pending' => 'bg-amber-50 text-amber-700 dark:bg-amber-950 dark:text-amber-300',
        'approved' => 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300',
        'rejected' => 'bg-red-50 text-red-700 dark:bg-red-950 dark:text-red-300',
    ];

    $canReview = app(\App\Services\BusinessAccessService::class)->can(auth()->user(), $business, 'products.update');
    $inputClass = 'w-full rounded-lg border p-2.5 dark:border-zinc-700 dark:bg-zinc-900';
@endphp

<x-layouts::app title="Payment Verification">
    <div id="main-content" class="mx-auto max-w-5xl space-y-6 p-4 md:p-6">
        <div class="flex flex-wrap items-end justify-between gap-3">
            <div>
                <h1 class="text-2xl font-bold md:text-3xl">UPI Payment Verifications</h1>
                <p class="text-sm text-zinc-500">{{ $business->name }}</p>
            </div>
            <a class="text-sm font-semibold text-emerald-700 underline dark:text-emerald-400"
                href="{{ route('businesses.orders.index', $business) }}" wire:navigate>← Back to orders</a>
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

        {{-- Filter --}}
        <form method="GET" class="flex flex-wrap items-center gap-3">
            <select name="status" aria-label="Filter by status"
                class="rounded-lg border p-2.5 dark:border-zinc-700 dark:bg-zinc-900">
                <option value="">All statuses</option>
                @foreach ($statuses as $status)
                    <option value="{{ $status }}" @selected(request('status') === $status)>{{ ucfirst($status) }}</option>
                @endforeach
            </select>
            <button type="submit" class="rounded-lg bg-emerald-700 px-5 py-2.5 font-semibold text-white hover:bg-emerald-800">Filter</button>
            @if (request()->filled('status'))
                <a href="{{ route('businesses.payments.index', $business) }}" class="text-sm underline">Reset</a>
            @endif
        </form>

        <div class="space-y-4">
            @forelse ($submissions as $item)
                <article class="space-y-4 rounded-2xl border bg-white p-5 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div class="min-w-0 space-y-1">
                            @if ($item->order)
                                <a href="{{ route('businesses.orders.show', [$business, $item->order]) }}" wire:navigate
                                    class="font-bold text-emerald-700 underline dark:text-emerald-400">{{ $item->order->order_number }}</a>
                                <p class="text-sm">{{ $item->order->customer_name }}</p>
                            @else
                                <p class="font-bold text-zinc-500">Order removed</p>
                            @endif

                            <p class="text-sm text-zinc-500">UTR: <span class="select-all font-mono text-zinc-800 dark:text-zinc-200">{{ $item->utr }}</span></p>
                            <p class="text-xs text-zinc-400">Submitted {{ $item->created_at?->format('d M Y, h:i A') }}</p>
                        </div>

                        <div class="space-y-2 text-right">
                            <p class="text-xl font-bold">₹{{ number_format((float) $item->amount, 2) }}</p>
                            <span class="inline-block rounded-full px-3 py-1 text-xs font-semibold {{ $badge[$item->status] ?? 'bg-zinc-100 text-zinc-600' }}">
                                {{ ucfirst($item->status) }}
                            </span>
                        </div>
                    </div>

                    @if ($item->proof_path && $item->order)
                        <a class="inline-flex items-center rounded-lg border px-3 py-1.5 text-sm font-semibold text-emerald-700 hover:border-emerald-600 dark:border-zinc-700 dark:text-emerald-400"
                            href="{{ route('businesses.payments.proof', [$business, $item->order, $item]) }}">
                            ⬇ Download proof
                        </a>
                    @endif

                    @if ($item->rejection_reason)
                        <p class="rounded-lg bg-red-50 p-3 text-sm text-red-700 dark:bg-red-950 dark:text-red-300">
                            <strong>Rejected:</strong> {{ $item->rejection_reason }}
                        </p>
                    @endif

                    @if ($item->status === 'pending' && $canReview && $item->order)
                        <form method="POST"
                            action="{{ route('businesses.payments.review', [$business, $item->order, $item]) }}"
                            class="grid gap-3 border-t pt-4 dark:border-zinc-700 md:grid-cols-4"
                            x-data="{ decision: 'approve' }">
                            @csrf @method('PATCH')

                            <label class="text-sm font-medium">Decision
                                <select name="decision" required x-model="decision" class="mt-1 {{ $inputClass }}">
                                    <option value="approve">Approve</option>
                                    <option value="reject">Reject</option>
                                </select>
                            </label>

                            <label class="text-sm font-medium md:col-span-2" x-show="decision === 'reject'" x-cloak>
                                Rejection reason
                                <input name="reason" maxlength="1000" :required="decision === 'reject'"
                                    placeholder="Customer ko kya wajah dikhani hai?" class="mt-1 {{ $inputClass }}">
                            </label>

                            <div class="flex items-end md:col-start-4">
                                <button type="submit" onclick="return confirm('Confirm payment decision?')"
                                    class="w-full rounded-lg bg-emerald-700 px-4 py-2.5 font-semibold text-white hover:bg-emerald-800">
                                    Save Decision
                                </button>
                            </div>
                        </form>
                    @endif
                </article>
            @empty
                <div class="rounded-2xl border border-dashed p-12 text-center dark:border-zinc-700">
                    <p class="font-semibold">No payment submissions found.</p>
                    @if (request()->filled('status'))
                        <p class="mt-1 text-sm text-zinc-500">Doosra status filter try karo.</p>
                    @endif
                </div>
            @endforelse
        </div>

        {{ $submissions->links() }}
    </div>
</x-layouts::app>