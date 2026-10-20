<?php

namespace App\Http\Controllers;

use App\Models\Business;
use App\Models\Order;
use App\Models\OrderPaymentSubmission;
use App\Services\BusinessAccessService;
use App\Services\GrowyaNotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class GrowyaPaymentController extends Controller
{
    private function customerOrder(Order $order): void
    {
        abort_unless(auth()->check() && (int) $order->customer_id === (int) auth()->id(), 403);
    }

    private function sellerOrder(Business $business, Order $order, string $permission): void
    {
        abort_unless((int) $order->business_id === (int) $business->id, 404);
        abort_unless(auth()->check() && app(BusinessAccessService::class)->can(auth()->user(), $business, $permission), 403);
    }

    private function submission(Order $order, OrderPaymentSubmission $submission): void
    {
        abort_unless((int) $submission->order_id === (int) $order->id, 404);
    }

    private function notifyUsers(array $userIds, string $type, string $title, string $message, string $url, int $businessId): void
    {
        try {
            app(GrowyaNotificationService::class)->send($userIds, $type, $title, $message, $url, $businessId);
        } catch (\Throwable $e) {
            Log::error('Growya payment notification failed', ['error' => $e->getMessage()]);
        }
    }

    public function customerIndex(Order $order)
    {
        $this->customerOrder($order);
        $submissions = OrderPaymentSubmission::where('order_id', $order->id)->latest()->get();

        return view('payments.customer', compact('order', 'submissions'));
    }

    public function submit(Request $request, Order $order)
    {
        $this->customerOrder($order);
        $data = $request->validate([
            'utr' => ['required', 'string', 'regex:/^[A-Za-z0-9-]{6,100}$/', 'unique:order_payment_submissions,utr'],
            'proof' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:5120'],
        ]);
        // Private local disk: no public storage link for customer payment proofs.
        $path = $request->hasFile('proof') ? $request->file('proof')->store('growya-payment-proofs', 'local') : null;
        try {
            DB::transaction(function () use ($order, $data, $path) {
                $locked = Order::whereKey($order->id)->lockForUpdate()->firstOrFail();
                if ((int) $locked->customer_id !== (int) auth()->id() || $locked->payment_method !== 'upi_manual' || $locked->payment_status !== 'pending' || in_array($locked->status, ['cancelled', 'delivered'], true)) {
                    throw ValidationException::withMessages(['utr' => 'Payment proof cannot be submitted for this order.']);
                }
                if (OrderPaymentSubmission::where('order_id', $locked->id)->where('status', 'pending')->exists()) {
                    throw ValidationException::withMessages(['utr' => 'An earlier payment proof is awaiting verification.']);
                }
                OrderPaymentSubmission::create([
                    'order_id' => $locked->id,
                    'submitted_by' => auth()->id(),
                    'utr' => strtoupper(trim($data['utr'])),
                    'proof_path' => $path,
                    'amount' => $locked->total_amount,
                    'status' => 'pending',
                ]);
            }, 3);
        } catch (\Throwable $e) {
            if ($path) {
                Storage::disk('local')->delete($path);
            }
            throw $e;
        }
        $this->notifyUsers(
            app(GrowyaNotificationService::class)->ownerOf($order->business_id),
            'order_payment_submitted',
            'UPI proof awaiting verification',
            'Payment proof submitted for order '.$order->order_number,
            route('businesses.payments.index', $order->business_id, false),
            $order->business_id
        );

        return redirect()->route('growya.payments.customer', $order)->with('success', 'Payment proof submitted. Seller verification is pending.');
    }

    public function sellerIndex(Business $business, Request $request)
    {
        abort_unless(auth()->check() && app(BusinessAccessService::class)->can(auth()->user(), $business, 'payments.view'), 403);
        $request->validate(['status' => ['nullable', 'in:pending,approved,rejected']]);
        $query = OrderPaymentSubmission::whereHas('order', fn ($q) => $q->where('business_id', $business->id))->with('order:id,business_id,order_number,customer_name,total_amount,payment_status');
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        $submissions = $query->latest('id')->paginate(20)->withQueryString();

        return view('businesses.payments.index', compact('business', 'submissions'));
    }

    public function review(Request $request, Business $business, Order $order, OrderPaymentSubmission $submission)
    {
        $this->sellerOrder($business, $order, 'payments.manage');
        $this->submission($order, $submission);
        $data = $request->validate(['decision' => ['required', 'in:approve,reject'], 'reason' => ['required_if:decision,reject', 'nullable', 'string', 'min:5', 'max:1000']]);
        DB::transaction(function () use ($order, $submission, $data) {
            $lockedOrder = Order::whereKey($order->id)->lockForUpdate()->firstOrFail();
            $locked = OrderPaymentSubmission::where('order_id', $lockedOrder->id)->whereKey($submission->id)->lockForUpdate()->firstOrFail();
            if ($locked->status !== 'pending' || $lockedOrder->payment_status !== 'pending' || $lockedOrder->payment_method !== 'upi_manual' || $lockedOrder->status === 'cancelled') {
                throw ValidationException::withMessages(['decision' => 'Payment is no longer awaiting verification.']);
            }
            if ($data['decision'] === 'approve') {
                $locked->update(['status' => 'approved', 'reviewed_by' => auth()->id(), 'reviewed_at' => now(), 'rejection_reason' => null]);
                $lockedOrder->update(['payment_status' => 'paid']);
            } else {
                $locked->update(['status' => 'rejected', 'reviewed_by' => auth()->id(), 'reviewed_at' => now(), 'rejection_reason' => trim($data['reason'])]);
            }
        }, 3);
        if ($order->customer_id) {
            $accepted = $data['decision'] === 'approve';
            $this->notifyUsers(
                [(int) $order->customer_id],
                $accepted ? 'order_payment_approved' : 'order_payment_rejected',
                $accepted ? 'UPI payment verified' : 'UPI payment proof rejected',
                'Order '.$order->order_number.($accepted ? ' payment verified.' : ' proof rejected. Please review the reason.'),
                route('growya.payments.customer', $order, false),
                $order->business_id
            );
        }

        return back()->with('success', 'Payment proof '.($data['decision'] === 'approve' ? 'approved' : 'rejected').'.');
    }

    public function proof(Business $business, Order $order, OrderPaymentSubmission $submission)
    {
        $this->sellerOrder($business, $order, 'payments.view');
        $this->submission($order, $submission);
        abort_unless($submission->proof_path, 404);
        abort_unless(Storage::disk('local')->exists($submission->proof_path), 404);

        return Storage::disk('local')->download($submission->proof_path);
    }

    public function customerProof(Order $order, OrderPaymentSubmission $submission)
    {
        $this->customerOrder($order);
        $this->submission($order, $submission);
        abort_unless($submission->proof_path && Storage::disk('local')->exists($submission->proof_path), 404);

        return Storage::disk('local')->download($submission->proof_path);
    }
}
