<?php

namespace App\Http\Controllers;

use App\Models\Business;
use App\Models\Order;
use App\Models\OrderRefundRecord;
use App\Models\OrderReturnRequest;
use App\Services\BusinessAccessService;
use App\Services\GrowyaOrderService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class GrowyaOrderReturnsController extends Controller
{
    private function customer(Order $order): void
    {
        abort_unless(auth()->check() && (int) $order->customer_id === (int) auth()->id(), 403);
    }

    private function seller(Business $business, string $permission): void
    {
        abort_unless(auth()->check() && app(BusinessAccessService::class)->can(auth()->user(), $business, $permission), 403);
    }

    public function customerIndex(Order $order)
    {
        $this->customer($order);
        $requests = OrderReturnRequest::where('order_id', $order->id)->with('refund')->latest()->get();

        return view('returns.customer', compact('order', 'requests'));
    }

    public function submit(Request $request, Order $order)
    {
        $this->customer($order);
        $data = $request->validate([
            'type' => ['required', Rule::in(['cancellation', 'return'])],
            'reason' => ['required', 'string', 'min:10', 'max:2500'],
        ]);
        DB::transaction(function () use ($order, $data) {
            $locked = Order::whereKey($order->id)->lockForUpdate()->firstOrFail();
            abort_unless((int) $locked->customer_id === (int) auth()->id(), 403);
            $allowed = $data['type'] === 'cancellation'
                ? in_array($locked->status, ['pending', 'confirmed'], true)
                : $locked->status === 'delivered';
            if (! $allowed) {
                throw ValidationException::withMessages(['type' => 'This request type is not available for the current order status.']);
            }
            if (OrderReturnRequest::where('order_id', $locked->id)->whereIn('status', ['pending', 'approved'])->exists()) {
                throw ValidationException::withMessages(['type' => 'An existing request is still in progress.']);
            }
            if (OrderReturnRequest::where('order_id', $locked->id)->whereIn('status', ['refunded', 'resolved'])->exists()) {
                throw ValidationException::withMessages(['type' => 'This order already has a finalized request.']);
            }
            OrderReturnRequest::create(['order_id' => $locked->id, 'customer_id' => auth()->id(),
                'type' => $data['type'], 'reason' => trim($data['reason']), 'status' => 'pending']);
        }, 3);

        return back()->with('success', 'Request submitted to seller. No refund is automatic.');
    }

    public function sellerIndex(Request $request, Business $business)
    {
        $this->seller($business, 'returns.view');
        $request->validate(['status' => ['nullable', Rule::in(['pending', 'approved', 'rejected', 'refunded', 'resolved'])]]);
        $requests = OrderReturnRequest::whereHas('order', fn ($q) => $q->where('business_id', $business->id))
            ->with(['order:id,business_id,order_number,status,payment_status,total_amount,customer_name', 'refund'])
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
            ->latest('id')->paginate(20)->withQueryString();

        return view('returns.seller', compact('business', 'requests'));
    }

    public function review(Request $request, Business $business, OrderReturnRequest $returnRequest, GrowyaOrderService $orderService)
    {
        $this->seller($business, 'returns.manage');
        $data = $request->validate([
            'decision' => ['required', Rule::in(['approve', 'reject'])],
            'seller_note' => ['required_if:decision,reject', 'nullable', 'string', 'max:2000'],
        ]);
        DB::transaction(function () use ($business, $returnRequest, $data, $orderService) {
            $order = Order::whereKey($returnRequest->order_id)->where('business_id', $business->id)->lockForUpdate()->firstOrFail();
            $locked = OrderReturnRequest::whereKey($returnRequest->id)->where('order_id', $order->id)->lockForUpdate()->firstOrFail();
            if ($locked->status !== 'pending') {
                throw ValidationException::withMessages(['decision' => 'Request already reviewed.']);
            }
            if ($locked->type === 'cancellation' && ! in_array($order->status, ['pending', 'confirmed'], true)) {
                throw ValidationException::withMessages(['decision' => 'Order can no longer be cancelled automatically.']);
            }
            if ($locked->type === 'return' && $order->status !== 'delivered') {
                throw ValidationException::withMessages(['decision' => 'Return is only valid for delivered orders.']);
            }
            $approved = $data['decision'] === 'approve';
            $locked->update(['status' => $approved ? 'approved' : 'rejected', 'seller_note' => $data['seller_note'] ?? null,
                'reviewed_by' => auth()->id(), 'reviewed_at' => now()]);
            if ($approved && $locked->type === 'cancellation' && $order->payment_status !== 'paid') {
                // Uses established cancellation service for exactly-once stock restoration.
                $orderService->cancel($business, $order);
                $locked->update(['status' => 'resolved']);
            }
        }, 3);

        return back()->with('success', 'Request reviewed.');
    }

    public function markRefunded(Request $request, Business $business, OrderReturnRequest $returnRequest, GrowyaOrderService $orderService)
    {
        $this->seller($business, 'returns.manage');
        $data = $request->validate([
            'method' => ['required', Rule::in(['manual_upi', 'bank_transfer', 'other'])],
            'reference' => ['required', 'string', 'min:6', 'max:120', 'regex:/^[A-Za-z0-9_\-\/]+$/', 'unique:order_refund_records,reference'],
            'note' => ['nullable', 'string', 'max:1000'],
        ]);
        DB::transaction(function () use ($business, $returnRequest, $orderService, $data) {
            $order = Order::whereKey($returnRequest->order_id)->where('business_id', $business->id)->lockForUpdate()->firstOrFail();
            $locked = OrderReturnRequest::whereKey($returnRequest->id)->where('order_id', $order->id)->lockForUpdate()->firstOrFail();
            if ($locked->status !== 'approved' || $order->payment_status !== 'paid') {
                throw ValidationException::withMessages(['reference' => 'Only approved requests for paid orders may be recorded as refunded.']);
            }
            if (OrderRefundRecord::where('order_id', $order->id)->exists()) {
                throw ValidationException::withMessages(['reference' => 'Refund already recorded for this order.']);
            }
            if ($locked->type === 'cancellation' && ! in_array($order->status, ['pending', 'confirmed'], true)) {
                throw ValidationException::withMessages(['reference' => 'Order no longer eligible for cancellation.']);
            }
            // The seller confirms transfer externally before recording this entry.
            OrderRefundRecord::create([
                'request_id' => $locked->id, 'order_id' => $order->id, 'amount' => $order->total_amount,
                'method' => $data['method'], 'reference' => strtoupper(trim($data['reference'])),
                'note' => $data['note'] ?? null, 'recorded_by' => auth()->id(), 'refunded_at' => now(),
            ]);
            $order->update(['payment_status' => 'refunded']);
            if ($locked->type === 'cancellation') {
                $orderService->cancel($business,$order);
            }
            // Delivered-return items require inspection and manual inventory adjustment.
            $locked->update(['status' => 'refunded']);
        }, 3);

        return back()->with('success','Refund recorded after external transfer confirmation.');
    }
}
