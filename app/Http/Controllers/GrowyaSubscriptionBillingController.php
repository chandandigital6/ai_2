<?php

namespace App\Http\Controllers;

use App\Models\Business;
use App\Models\GrowyaSubscriptionBillingDocument;
use App\Models\SubscriptionPayment;
use App\Services\BusinessAccessService;
use App\Services\GrowyaSubscriptionBillingService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class GrowyaSubscriptionBillingController extends Controller
{
    private function admin(): void
    {
        abort_unless(auth()->user()?->hasRole('super_admin', 'web'), 403);
    }

    private function owner(Business $business): void
    {
        abort_unless(auth()->user() && app(BusinessAccessService::class)->can(auth()->user(), $business, 'billing.view'), 403);
    }

    private function authorizeDoc(GrowyaSubscriptionBillingDocument $document): void
    {
        $document->loadMissing('business');
        abort_unless($document->business, 404);
        $this->owner($document->business);
    }

    public function adminIndex(Request $request)
    {
        $this->admin();

        $docs = $this->filter($request, GrowyaSubscriptionBillingDocument::query()->with('business:id,name'))
            ->paginate(25)
            ->withQueryString();

        $unissued = SubscriptionPayment::query()
            ->whereIn('status', ['admin_recorded', 'waived'])
            ->whereNotIn('id', GrowyaSubscriptionBillingDocument::select('subscription_payment_id'))
            ->with('subscription.business:id,name')
            ->latest('id')
            ->paginate(15, ['*'], 'payments_page')
            ->withQueryString();

        return view('growya-billing.index', compact('docs', 'unissued'));
    }

    public function ownerIndex(Request $request, Business $business)
    {
        $this->owner($business);

        $docs = $this->filter($request, GrowyaSubscriptionBillingDocument::where('business_id', $business->id))
            ->paginate(25)
            ->withQueryString();

        return view('growya-billing.index', [
            'docs' => $docs,
            'business' => $business,
            'unissued' => null,
        ]);
    }

    private function filter(Request $request, $query)
    {
        $request->validate([
            'from' => 'nullable|date',
            'to' => 'nullable|date|after_or_equal:from',
        ]);

        return $query
            ->when($request->filled('from'), fn ($q) => $q->whereDate('issued_at', '>=', $request->input('from')))
            ->when($request->filled('to'), fn ($q) => $q->whereDate('issued_at', '<=', $request->input('to')))
            ->latest('issued_at');
    }

    public function issue(SubscriptionPayment $payment, GrowyaSubscriptionBillingService $billing)
    {
        $this->admin();
        $doc = $billing->issue($payment->id, auth()->id());

        return redirect()->route('growya.billing.show', $doc)->with('success', 'Billing document available.');
    }

    public function show(GrowyaSubscriptionBillingDocument $document)
    {
        $this->authorizeDoc($document);

        return view('growya-billing.document', ['doc' => $document]);
    }

    public function pdf(GrowyaSubscriptionBillingDocument $document)
    {
        $this->authorizeDoc($document);
        abort_unless(class_exists(Pdf::class), 503, 'Install barryvdh/laravel-dompdf first.');

        $filename = Str::slug($document->document_number ?: 'document-'.$document->id).'.pdf';

        return Pdf::loadView('growya-billing.document', [
            'doc' => $document,
            'isPdf' => true,
        ])->setPaper('a4')->download($filename);
    }

    public function export(Request $request)
    {
        $this->admin();
        $docs = $this->filter($request, GrowyaSubscriptionBillingDocument::query())->cursor();

        return response()->streamDownload(function () use ($docs) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Document', 'Issued', 'Business', 'Type', 'Amount', 'Currency', 'Payment status']);

            foreach ($docs as $d) {
                fputcsv($out, [
                    $d->document_number,
                    $d->issued_at?->toDateString(),
                    $d->business_name,
                    $d->document_type,
                    $d->amount,
                    $d->payment_currency,
                    $d->payment_status_snapshot,
                ]);
            }

            fclose($out);
        }, 'growya-subscription-billing.csv', ['Content-Type' => 'text/csv']);
    }
}
