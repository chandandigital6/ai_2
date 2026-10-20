<!DOCTYPE html>
<html lang="en" class="h-full">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $doc->document_number ?? 'Billing Document' }}</title>
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    colors: {
                        emerald: { 50: '#ecfdf5', 100: '#d1fae5', 500: '#10b981', 600: '#059669', 700: '#047857' }
                    }
                }
            }
        }
    </script>
    <style>
        @media print {
            body { background: white !important; padding: 0 !important; }
            .no-print { display: none !important; }
            .print-shadow { box-shadow: none !important; border: 1px solid #e4e4e7 !important; }
        }
    </style>
</head>

<body class="bg-zinc-50 font-sans text-zinc-900 antialiased min-h-full py-10 px-4 sm:px-6 lg:px-8">

    <div class="mx-auto max-w-3xl space-y-6">

        {{-- Top Actions Bar (Hidden in PDF / Print) --}}
        @if (empty($isPdf))
            <div class="no-print flex items-center justify-between gap-4">
                <a href="{{ url()->previous() }}"
                   class="inline-flex items-center gap-2 rounded-xl border border-zinc-300 bg-white px-4 py-2 text-sm font-medium text-zinc-700 shadow-2xs hover:bg-zinc-50 transition-colors">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18" /></svg>
                    Back
                </a>
                <div class="flex items-center gap-3">
                    <button onclick="window.print()"
                            class="inline-flex items-center gap-2 rounded-xl border border-zinc-300 bg-white px-4 py-2 text-sm font-medium text-zinc-700 shadow-2xs hover:bg-zinc-50 transition-colors">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6.72 13.829c-.24.03-.48.062-.72.096m.72-.096a42.415 42.415 0 0 1 10.56 0m-10.56 0L6.34 18m10.94-4.171c.24.03.48.062.72.096m-.72-.096L17.66 18m0 0 .229 2.523a1.125 1.125 0 0 1-1.12 1.227H7.231c-.662 0-1.18-.568-1.12-1.227L6.34 18m11.318 0h1.091A2.25 2.25 0 0 0 21 15.75V9.456c0-1.081-.768-2.015-1.837-2.175a48.055 48.055 0 0 0-1.913-.247M6.34 18H5.25A2.25 2.25 0 0 1 3 15.75V9.456c0-1.081.768-2.015 1.837-2.175a48.041 48.041 0 0 1 1.913-.247m10.5 0a48.536 48.536 0 0 0-10.5 0m10.5 0V3.375c0-.621-.504-1.125-1.125-1.125h-4.25c-.621 0-1.125.504-1.125 1.125v3.659M18 10.5h.008v.008H18V10.5Zm-3 0h.008v.008H15V10.5Z" /></svg>
                        Print
                    </button>
                    <a href="{{ route('growya.billing.pdf', $doc) }}"
                       class="inline-flex items-center gap-2 rounded-xl bg-emerald-600 px-4 py-2 text-sm font-semibold text-white shadow-xs hover:bg-emerald-500 transition-colors">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3" /></svg>
                        Download PDF
                    </a>
                </div>
            </div>
        @endif

        {{-- Main Document Card --}}
        <div class="print-shadow rounded-3xl border border-zinc-200 bg-white p-6 sm:p-10 shadow-sm space-y-8">

            {{-- Header --}}
            <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4 border-b border-zinc-200 pb-6">
                <div>
                    <span class="inline-flex items-center rounded-md bg-emerald-50 px-2.5 py-1 text-xs font-semibold text-emerald-700 uppercase tracking-wide">
                        {{ str_replace('_', ' ', $doc->document_type) }}
                    </span>
                    <h1 class="mt-2 text-xl sm:text-2xl font-bold tracking-tight text-zinc-900">
                        {{ $doc->document_type === 'manual_receipt' ? 'Manual Payment Record Receipt' : 'Complimentary Subscription Statement' }}
                    </h1>
                </div>
                <div class="text-left sm:text-right">
                    <p class="text-sm font-bold text-zinc-900">{{ $doc->document_number }}</p>
                    <p class="text-xs text-zinc-500">Issued on {{ $doc->issued_at?->format('d M Y, H:i') }}</p>
                </div>
            </div>

            {{-- Notice / Hint Box --}}
            <div class="rounded-2xl bg-amber-50 border border-amber-200 p-4 text-xs sm:text-sm text-amber-800 space-y-1">
                <div class="flex items-center gap-2 font-semibold">
                    <svg class="h-4 w-4 shrink-0 text-amber-600" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" /></svg>
                    Not a GST Tax Invoice
                </div>
                <p class="text-amber-700 pl-6">
                    This document reflects an administrator-entered payment record and is not an independent bank/gateway settlement confirmation. No GST has been calculated.
                </p>
            </div>

            {{-- Issuer & Billed Business Info Grid --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-6 text-sm">
                <div class="rounded-2xl bg-zinc-50 p-4 border border-zinc-100 space-y-1">
                    <span class="text-xs font-semibold uppercase tracking-wider text-zinc-400">Issued By</span>
                    <p class="font-semibold text-zinc-900">{{ $doc->issuer_name }}</p>
                    <p class="text-xs text-zinc-600 leading-relaxed">{{ $doc->issuer_address }}</p>
                    @if ($doc->issuer_gstin)
                        <p class="text-xs font-medium text-zinc-700 pt-1">GSTIN: <span class="font-mono text-zinc-900">{{ $doc->issuer_gstin }}</span></p>
                    @endif
                </div>

                <div class="rounded-2xl bg-zinc-50 p-4 border border-zinc-100 space-y-1">
                    <span class="text-xs font-semibold uppercase tracking-wider text-zinc-400">Billed Business</span>
                    <p class="font-semibold text-zinc-900">{{ $doc->business_name }}</p>
                    <p class="text-xs text-zinc-600 leading-relaxed">{{ $doc->business_address }}</p>
                    @if ($doc->business_gstin)
                        <p class="text-xs font-medium text-zinc-700 pt-1">GSTIN: <span class="font-mono text-zinc-900">{{ $doc->business_gstin }}</span></p>
                    @endif
                </div>
            </div>

            {{-- Details Table --}}
            <div class="overflow-hidden rounded-2xl border border-zinc-200">
                <table class="w-full text-left text-sm">
                    <tbody class="divide-y divide-zinc-200">
                        <tr class="hover:bg-zinc-50/50">
                            <th class="px-4 py-3 font-medium text-zinc-500 w-1/3">Subscription Plan</th>
                            <td class="px-4 py-3 font-semibold text-zinc-900">{{ $doc->plan_name }}</td>
                        </tr>
                        <tr class="hover:bg-zinc-50/50">
                            <th class="px-4 py-3 font-medium text-zinc-500">Billing Cycle</th>
                            <td class="px-4 py-3 text-zinc-800 capitalize">{{ $doc->billing_cycle ?? 'N/A' }}</td>
                        </tr>
                        <tr class="hover:bg-zinc-50/50">
                            <th class="px-4 py-3 font-medium text-zinc-500">Service Period</th>
                            <td class="px-4 py-3 text-zinc-800">
                                @if($doc->service_starts_at && $doc->service_ends_at)
                                    {{ $doc->service_starts_at->format('d M Y') }} – {{ $doc->service_ends_at->format('d M Y') }}
                                @else
                                    N/A
                                @endif
                            </td>
                        </tr>
                        <tr class="hover:bg-zinc-50/50">
                            <th class="px-4 py-3 font-medium text-zinc-500">Payment Method</th>
                            <td class="px-4 py-3 text-zinc-800 uppercase text-xs font-semibold">{{ $doc->payment_method_snapshot ?? 'N/A' }}</td>
                        </tr>
                        <tr class="hover:bg-zinc-50/50">
                            <th class="px-4 py-3 font-medium text-zinc-500">Transaction Reference</th>
                            <td class="px-4 py-3 font-mono text-xs text-zinc-700">{{ $doc->payment_reference_snapshot ?: 'Not supplied' }}</td>
                        </tr>
                        <tr class="hover:bg-zinc-50/50">
                            <th class="px-4 py-3 font-medium text-zinc-500">Payment Status</th>
                            <td class="px-4 py-3">
                                <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold bg-emerald-50 text-emerald-700 uppercase">
                                    {{ $doc->payment_status_snapshot }}
                                </span>
                            </td>
                        </tr>
                        <tr class="hover:bg-zinc-50/50">
                            <th class="px-4 py-3 font-medium text-zinc-500">Recorded Payment Date</th>
                            <td class="px-4 py-3 text-zinc-800">{{ $doc->recorded_payment_at?->format('d M Y, H:i') ?: 'Not provided' }}</td>
                        </tr>
                        <tr class="hover:bg-zinc-50/50">
                            <th class="px-4 py-3 font-medium text-zinc-500">GST Applied</th>
                            <td class="px-4 py-3 text-zinc-500 text-xs italic">Not calculated / not included</td>
                        </tr>
                        <tr class="bg-zinc-50/80 font-bold">
                            <th class="px-4 py-4 text-zinc-900 text-base">Recorded Amount</th>
                            <td class="px-4 py-4 text-emerald-600 text-lg">
                                {{ $doc->payment_currency }} {{ number_format((float) $doc->amount, 2) }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            {{-- Footer Legal Note --}}
            <div class="border-t border-zinc-200 pt-4 text-xs text-zinc-400 leading-relaxed">
                <p>This immutable record was issued from the subscription payment record at the time shown above. It cannot be used as a tax invoice. For any required GST tax invoice, configure legally compliant invoicing separately.</p>
            </div>

        </div>
    </div>

</body>
</html>
