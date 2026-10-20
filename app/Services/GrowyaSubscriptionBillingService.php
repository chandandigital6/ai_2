<?php
namespace App\Services;

use App\Models\GrowyaSubscriptionBillingDocument;
use App\Models\SubscriptionPayment;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class GrowyaSubscriptionBillingService
{
    public function issue(int $paymentId, int $actorId): GrowyaSubscriptionBillingDocument
    {
        return DB::transaction(function () use ($paymentId, $actorId) {
            $payment = SubscriptionPayment::query()->lockForUpdate()->findOrFail($paymentId);
            $existing = GrowyaSubscriptionBillingDocument::where('subscription_payment_id', $paymentId)->first();
            if ($existing) return $existing;
            if (!in_array($payment->status, ['admin_recorded', 'waived'], true)) {
                throw ValidationException::withMessages(['payment' => 'Only admin-recorded or waived payments may be documented.']);
            }
            if (trim((string) config('growya_billing.issuer_name')) === '') {
                throw ValidationException::withMessages(['issuer' => 'Set GROWYA_BILLING_ISSUER_NAME before issuing documents.']);
            }
            $sub = $payment->subscription()->with(['business', 'plan'])->firstOrFail();
            $business = $sub->business;
            if (!$business || !$sub->plan) throw ValidationException::withMessages(['payment' => 'Missing linked business or plan.']);
            $waived = $payment->status === 'waived';
            $doc = GrowyaSubscriptionBillingDocument::create([
                'subscription_payment_id' => $payment->id, 'subscription_id' => $sub->id,
                'business_id' => $business->id, 'issued_by' => $actorId,
                'document_type' => $waived ? 'complimentary_statement' : 'manual_receipt',
                'payment_status_snapshot' => $payment->status,
                'payment_method_snapshot' => $payment->payment_method,
                'payment_reference_snapshot' => $payment->transaction_id,
                'payment_currency' => $payment->currency ?: 'INR',
                'amount' => $waived ? 0 : $payment->amount, 'tax_amount' => 0,
                'issuer_name' => config('growya_billing.issuer_name'),
                'issuer_address' => config('growya_billing.issuer_address'),
                'issuer_gstin' => config('growya_billing.issuer_gstin'),
                'business_name' => $business->name, 'business_address' => $business->address,
                'business_gstin' => $business->gst_number, 'plan_name' => $sub->plan->name,
                'billing_cycle' => $sub->billing_cycle, 'service_starts_at' => $sub->starts_at,
                'service_ends_at' => $sub->ends_at, 'recorded_payment_at' => $payment->paid_at,
                'issued_at' => now(),
            ]);
            $doc->document_number = 'GR-BILL-' . $doc->issued_at->format('Y') . '-' . str_pad((string)$doc->id, 7, '0', STR_PAD_LEFT);
            $doc->save();
            DB::afterCommit(function () use ($business, $doc) {
                try {
                    $notify = app(\App\Services\GrowyaNotificationService::class);
                    $notify->send($notify->ownerOf($business->id), 'subscription_billing_issued',
                        'Subscription billing document ready',
                        'Document ' . $doc->document_number . ' is ready to download.',
                        '/businesses/' . $business->id . '/billing', $business->id);
                } catch (\Throwable $e) { report($e); }
            });
            return $doc;
        }, 3);
    }
}
