<?php

namespace App\Http\Controllers;

use App\Models\Business;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\SubscriptionPayment;
use App\Services\BusinessAccessService;
use App\Services\GrowyaNotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class GrowyaSubscriptionController extends Controller
{
    private function admin(): void
    {
        abort_unless(auth()->user()?->hasRole('super_admin', 'web'), 403);
    }

    private function owner(Business $business): void
    {
        abort_unless(auth()->user() && app(BusinessAccessService::class)->can(auth()->user(), $business, 'subscriptions.view'), 403);
    }

    public function index(Business $business)
    {
        $this->owner($business);
        $plans = Plan::where('is_active', true)->orderBy('sort_order')->get();
        $history = Subscription::where('business_id', $business->id)->with('plan')->latest('id')->get();

        return view('growya-plans.business', compact('business', 'plans', 'history'));
    }

    public function request(Request $request, Business $business, GrowyaNotificationService $notifications)
    {
        $this->owner($business);
        $data = $request->validate(['plan_id' => ['required', 'integer', 'exists:plans,id'], 'billing_cycle' => ['required', Rule::in(['monthly', 'yearly'])]]);
        $sub = DB::transaction(function () use ($business, $data) {
            Business::whereKey($business->id)->lockForUpdate()->firstOrFail();
            if (Subscription::where('business_id', $business->id)->where('status', 'requested')->exists()) {
                throw ValidationException::withMessages(['plan_id' => 'A subscription request is already pending.']);
            }
            $plan = Plan::whereKey($data['plan_id'])->where('is_active', true)->firstOrFail();
            $amount = $data['billing_cycle'] === 'yearly' ? $plan->yearly_price : $plan->monthly_price;

            return Subscription::create([
                'business_id' => $business->id,
                'plan_id' => $plan->id,
                'billing_cycle' => $data['billing_cycle'],
                'amount' => $amount,
                'starts_at' => now(),
                'ends_at' => null,
                'trial_ends_at' => null,
                'status' => 'requested',
            ]);
        });
        $notifications->send(
            $notifications->superAdmins(),
            'subscription_requested',
            'New subscription request',
            $business->name.' requested subscription #'.$sub->id,
            '/admin/subscriptions',
            $business->id
        );

        return back()->with('success', 'Request submitted. Your plan is not active until admin confirmation.');
    }

    public function adminIndex(Request $request)
    {
        $this->admin();
        $status = $request->query('status');
        $subscriptions = Subscription::with([
            'business:id,name,owner_id',
            'plan:id,name',
            'subscription_payments', // yahan change karein
        ])
            ->when(in_array($status, ['requested', 'active', 'rejected', 'expired', 'superseded'], true), fn ($q) => $q->where('status', $status))
            ->latest('id')
            ->paginate(20)
            ->withQueryString();

        return view('growya-plans.requests', compact('subscriptions'));
    }

    public function review(Request $request, Subscription $subscription, GrowyaNotificationService $notifications)
    {
        $this->admin();
        $data = $request->validate([
            'decision' => ['required', Rule::in(['approve', 'reject'])],
            'payment_method' => ['nullable', Rule::in(['cash', 'upi', 'bank_transfer', 'complimentary'])],
            'transaction_id' => ['nullable', 'string', 'max:150'],
            'note' => ['nullable', 'string', 'max:500'],
        ]);
        if ($data['decision'] === 'approve' && empty($data['payment_method'])) {
            throw ValidationException::withMessages(['payment_method' => 'Specify payment method, or complimentary for a waived fee.']);
        }
        $businessId = $subscription->business_id;
        DB::transaction(function () use ($subscription, $data) {
            // Serialize review per business to avoid two concurrent active plans.
            Business::whereKey($subscription->business_id)->lockForUpdate()->firstOrFail();
            $locked = Subscription::whereKey($subscription->id)->lockForUpdate()->firstOrFail();
            if ($locked->status !== 'requested') {
                throw ValidationException::withMessages(['decision' => 'This request was already reviewed.']);
            }
            if ($data['decision'] === 'reject') {
                $locked->update(['status' => 'rejected']);

                return;
            }
            $starts = now();
            $ends = $locked->billing_cycle === 'yearly' ? $starts->copy()->addYear() : $starts->copy()->addMonth();
            $locked->update(['status' => 'active', 'starts_at' => $starts, 'ends_at' => $ends]);
            // Close former active plans rather than leaving overlapping access.
            Subscription::where('business_id', $locked->business_id)->where('status', 'active')->whereKeyNot($locked->id)->update(['status' => 'superseded', 'ends_at' => $starts]);
            // Record manual admin confirmation; no claim of gateway/bank verification.
            SubscriptionPayment::create([
                'subscription_id' => $locked->id,
                'amount' => $locked->amount,
                'currency' => 'INR',
                'payment_method' => $data['payment_method'],
                'transaction_id' => $data['transaction_id'] ?? null,
                'status' => $data['payment_method'] === 'complimentary' ? 'waived' : 'admin_recorded',
                'paid_at' => $starts,
                'metadata' => ['recorded_by' => auth()->id(), 'note' => $data['note'] ?? null, 'verification' => 'manual_admin_confirmation'],
            ]);
        });
        $notifications->send(
            $notifications->ownerOf($businessId),
            'subscription_reviewed',
            'Subscription '.($data['decision'] === 'approve' ? 'activated' : 'rejected'),
            'Your subscription request #'.$subscription->id.' was '.($data['decision'] === 'approve' ? 'activated' : 'rejected').'.',
            '/businesses/'.$businessId.'/subscriptions',
            $businessId
        );

        return back()->with('success', 'Subscription request reviewed.');
    }
}
