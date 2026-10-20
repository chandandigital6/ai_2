@php
    $isAdminView = !isset($business);

    $typeLabel = [
        'manual_receipt' => 'Manual receipt',
        'complimentary_statement' => 'Complimentary statement',
    ];

    $typeBadge = [
        'manual_receipt' => 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300',
        'complimentary_statement' => 'bg-blue-50 text-blue-700 dark:bg-blue-950 dark:text-blue-300',
    ];

    $statusBadge = [
        'admin_recorded' => 'bg-amber-50 text-amber-700 dark:bg-amber-950 dark:text-amber-300',
        'waived' => 'bg-blue-50 text-blue-700 dark:bg-blue-950 dark:text-blue-300',
    ];

    $label = fn($v) => ucfirst(str_replace('_', ' ', (string) $v));
    $hasFilter = request()->filled('from') || request()->filled('to');

    $input = 'rounded-lg border border-zinc-300 bg-transparent px-3 py-2 dark:border-zinc-700';
    $linkBtn = 'inline-flex rounded-lg border px-3 py-1.5 text-xs font-semibold text-emerald-700 hover:border-emerald-600 dark:border-zinc-700 dark:text-emerald-400';
@endphp

<x-layouts::app :title="'Subscription Billing'" :business="$business ?? null">
    <main id="main-content" class="mx-auto max-w-6xl space-y-6 p-4 md:p-8">
        <header class="flex flex-wrap items-end justify-between gap-3">
            <div>
                <p class="text-sm font-semibold text-emerald-700 dark:text-emerald-400">
                    {{ $isAdminView ? 'Administration' : $business->name }}
                </p>
                <h1 class="text-2xl font-bold md:text-3xl">Subscription Billing</h1>
                <p class="mt-1 text-sm text-zinc-500">Manual receipts aur complimentary statements. Ye GST tax invoice nahi hain.</p>
            </div>

            @if ($isAdminView)
                <a href="{{ route('growya.billing.export', request()->only('from', 'to')) }}"
                    class="inline-flex items-center gap-2 rounded-xl border px-4 py-2.5 text-sm font-semibold hover:border-emerald-600 hover:text-emerald-700 dark:border-zinc-700">
                    ⬇ Export CSV
                </a>
            @endif
        </header>

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
        <form method="GET"
            class="flex flex-wrap items-end gap-3 rounded-2xl border bg-white p-4 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
            <label class="grid gap-1 text-sm font-medium">From
                <input class="{{ $input }}" type="date" name="from" value="{{ request('from') }}">
            </label>
            <label class="grid gap-1 text-sm font-medium">To
                <input class="{{ $input }}" type="date" name="to" value="{{ request('to') }}">
            </label>
            <button type="submit" class="rounded-lg bg-emerald-700 px-5 py-2.5 font-semibold text-white hover:bg-emerald-800">Filter</button>
            @if ($hasFilter)
                <a href="{{ url()->current() }}" class="pb-2.5 text-sm underline">Reset</a>
            @endif
        </form>

        {{-- Documents --}}
        <section class="overflow-x-auto rounded-2xl border bg-white shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
            <table class="w-full text-left text-sm">
                <thead class="bg-zinc-50 text-xs uppercase tracking-wide text-zinc-500 dark:bg-zinc-800">
                    <tr>
                        <th class="p-3 font-semibold">Document</th>
                        <th class="p-3 font-semibold">Business</th>
                        <th class="p-3 font-semibold">Issued</th>
                        <th class="p-3 font-semibold">Type</th>
                        <th class="p-3 text-right font-semibold">Amount</th>
                        <th class="p-3"></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($docs as $doc)
                        <tr class="border-t transition hover:bg-zinc-50 dark:border-zinc-700 dark:hover:bg-zinc-800">
                            <td class="whitespace-nowrap p-3 font-mono text-xs font-semibold">{{ $doc->document_number }}</td>
                            <td class="p-3">{{ $doc->business_name }}</td>
                            <td class="whitespace-nowrap p-3 text-zinc-500">{{ $doc->issued_at?->format('d M Y') }}</td>
                            <td class="p-3">
                                <span class="whitespace-nowrap rounded-full px-2.5 py-1 text-xs font-semibold {{ $typeBadge[$doc->document_type] ?? 'bg-zinc-100 text-zinc-600' }}">
                                    {{ $typeLabel[$doc->document_type] ?? $label($doc->document_type) }}
                                </span>
                            </td>
                            <td class="whitespace-nowrap p-3 text-right font-semibold">
                                {{ $doc->payment_currency }} {{ number_format((float) $doc->amount, 2) }}
                            </td>
                            <td class="whitespace-nowrap p-3 text-right">
                                <div class="flex justify-end gap-2">
                                    <a class="{{ $linkBtn }}" href="{{ route('growya.billing.show', $doc) }}">View</a>
                                    <a class="{{ $linkBtn }}" href="{{ route('growya.billing.pdf', $doc) }}">⬇ PDF</a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="p-10 text-center">
                                <p class="font-semibold">No billing documents yet.</p>
                                <p class="mt-1 text-sm text-zinc-500">
                                    {{ $hasFilter ? 'Is date range mein koi document nahi hai.' : 'Payment record hone par document yahan dikhega.' }}
                                </p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </section>

        {{ $docs->links() }}

        {{-- Unissued (admin only) --}}
        @if ($unissued)
            <section class="space-y-3">
                <div>
                    <h2 class="text-lg font-bold">Documents jo abhi issue nahi hue</h2>
                    <p class="text-sm text-zinc-500">Admin-recorded ya waived payments jinke liye billing document baaki hai.</p>
                </div>

                <div class="overflow-x-auto rounded-2xl border bg-white shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
                    <table class="w-full text-left text-sm">
                        <thead class="bg-zinc-50 text-xs uppercase tracking-wide text-zinc-500 dark:bg-zinc-800">
                            <tr>
                                <th class="p-3 font-semibold">Payment</th>
                                <th class="p-3 font-semibold">Business</th>
                                <th class="p-3 font-semibold">Status</th>
                                <th class="p-3 text-right font-semibold">Amount</th>
                                <th class="p-3"></th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($unissued as $payment)
                                <tr class="border-t dark:border-zinc-700">
                                    <td class="whitespace-nowrap p-3 text-zinc-500">#{{ $payment->id }}</td>
                                    <td class="p-3 font-semibold">{{ $payment->subscription?->business?->name ?? 'Unavailable' }}</td>
                                    <td class="p-3">
                                        <span class="rounded-full px-2.5 py-1 text-xs font-semibold {{ $statusBadge[$payment->status] ?? 'bg-zinc-100 text-zinc-600' }}">
                                            {{ $label($payment->status) }}
                                        </span>
                                    </td>
                                    <td class="whitespace-nowrap p-3 text-right font-semibold">
                                        {{ $payment->currency }} {{ number_format((float) $payment->amount, 2) }}
                                    </td>
                                    <td class="p-3 text-right">
                                        <form method="POST" action="{{ route('growya.billing.issue', $payment) }}">
                                            @csrf
                                            <button type="submit"
                                                onclick="return confirm('Is admin-recorded payment se immutable billing document issue karna hai?')"
                                                class="rounded-lg bg-emerald-700 px-3 py-1.5 text-xs font-semibold text-white hover:bg-emerald-800">
                                                Issue document
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="p-8 text-center text-zinc-500">Koi eligible payment nahi hai. Sab documents issue ho chuke hain.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                {{ $unissued->links() }}
            </section>
        @endif
    </main>
</x-layouts::app>
