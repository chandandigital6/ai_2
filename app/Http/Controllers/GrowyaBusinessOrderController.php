<?php

namespace App\Http\Controllers;

use App\Models\Business;
use App\Models\Order;
use App\Services\BusinessAccessService;
use App\Services\Extensions\DeliveryService;
use App\Services\GrowyaOrderService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class GrowyaBusinessOrderController extends Controller
{
    private function authorizeBusiness(Business $business, string $permission): void
    {
        abort_unless(auth()->check() && app(BusinessAccessService::class)->can(auth()->user(), $business, $permission), 403);
    }

    private function scope(Business $business, Order $order): void
    {
        abort_unless((int) $order->business_id === (int) $business->id, 404);
    }

    public function index(Request $request, Business $business)
    {
        $this->authorizeBusiness($business, 'orders.view');
        $request->validate(['status' => ['nullable', Rule::in(['pending', 'confirmed', 'shipped', 'delivered', 'cancelled'])], 'search' => ['nullable', 'string', 'max:100']]);
        $query = Order::where('business_id', $business->id);
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('search')) {
            $query->where(fn ($q) => $q->where('order_number', 'like', '%'.$request->search.'%')->orWhere('customer_name', 'like', '%'.$request->search.'%')->orWhere('customer_phone', 'like', '%'.$request->search.'%'));
        }
        $orders = $query->latest('id')->paginate(20)->withQueryString();

        return view('businesses.orders.index', compact('business', 'orders'));
    }

    public function show(Business $business, Order $order)
    {
        $this->authorizeBusiness($business, 'orders.view');
        $this->scope($business, $order);
        $order->load('items');

        return view('businesses.orders.show', compact('business', 'order'));
    }

    public function status(Request $request, Business $business, Order $order, GrowyaOrderService $service)
    {
        $this->authorizeBusiness($business, 'orders.manage');
        $this->scope($business, $order);
        $data = $request->validate(['status' => ['required', Rule::in(['confirmed', 'shipped', 'delivered', 'cancelled'])]]);
        if ($data['status'] === 'cancelled') {
            $service->cancel($business, $order);

            return back()->with('success', 'Order cancelled and stock restored.');
        }
        $allowed = ['pending' => ['confirmed'], 'confirmed' => ['shipped'], 'shipped' => ['delivered']];
        $changed = DB::transaction(function () use ($business, $order, $data, $allowed) {
            $locked = Order::where('business_id', $business->id)->whereKey($order->id)->lockForUpdate()->firstOrFail();
            if (! in_array($data['status'], $allowed[$locked->status] ?? [], true)) {
                throw ValidationException::withMessages(['status' => 'Invalid order status transition.']);
            }
            if (($data['status'] ?? $data['stage'] ?? null) === 'delivered') {
                app(DeliveryService::class)->assertCompletion($locked);
            }
            $locked->update(['status' => $data['status']]);

            return true;
        }, 3);

        return back()->with('success', 'Order status updated.');
    }
}
