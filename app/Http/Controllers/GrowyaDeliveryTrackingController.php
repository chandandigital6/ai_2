<?php

namespace App\Http\Controllers;

use App\Models\Business;
use App\Models\GrowyaOrderShipment;
use App\Models\GrowyaOrderTrackingEvent;
use App\Models\Order;
use App\Services\BusinessAccessService;
use App\Services\Extensions\DeliveryService;
use App\Services\GrowyaNotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class GrowyaDeliveryTrackingController extends Controller
{
    private const STAGES = ['placed', 'confirmed', 'packed', 'shipped', 'out_for_delivery', 'delivered'];

    private function owner(Order $order): void
    {
        abort_unless(auth()->check() && (int) $order->customer_id === (int) auth()->id(), 403);
    }

    private function seller(Business $business, Order $order, string $permission): void
    {
        abort_unless((int) $order->business_id === (int) $business->id, 404);
        abort_unless(auth()->check() && app(BusinessAccessService::class)->can(auth()->user(), $business, $permission), 403);
    }

    private function timeline(Order $order): array
    {
        $shipment = GrowyaOrderShipment::where('order_id', $order->id)->first();
        $stage = $shipment?->stage ?? match ($order->status) {
            'delivered' => 'delivered','shipped' => 'shipped','confirmed' => 'confirmed',default => 'placed'
        };
        $events = GrowyaOrderTrackingEvent::with('actor:id,name')->where('order_id', $order->id)->orderBy('id')->get();

        return compact('shipment', 'stage', 'events');
    }

    public function customer(Order $order)
    {
        $this->owner($order);
        $data = $this->timeline($order);

        return view('delivery.customer', ['order' => $order] + $data);
    }

    public function sellerShow(Business $business, Order $order)
    {
        $this->seller($business, $order, 'delivery.view');
        $data = $this->timeline($order);

        return view('delivery.seller', ['business' => $business, 'order' => $order] + $data);
    }

    public function index(Request $request, Business $business)
    {
        abort_unless(auth()->check() && app(BusinessAccessService::class)->can(auth()->user(), $business, 'delivery.view'), 403);
        $request->validate(['filter' => ['nullable', Rule::in(['all', 'dispatch', 'transit', 'delivered'])]]);
        $filter = $request->input('filter', 'all');
        $q = Order::query()->where('business_id', $business->id)->whereNotIn('status', ['cancelled']);
        if ($filter === 'dispatch') {
            $q->where('status', 'confirmed');
        }
        if ($filter === 'transit') {
            $q->where('status', 'shipped');
        }
        if ($filter === 'delivered') {
            $q->where('status', 'delivered');
        }

        return view('delivery.index', ['business' => $business, 'orders' => $q->latest('id')->paginate(20)->withQueryString(), 'filter' => $filter]);
    }

    public function update(Request $request, Business $business, Order $order)
    {
        $this->seller($business, $order, 'delivery.manage');
        $data = $request->validate([
            'stage' => ['required', Rule::in(self::STAGES)],
            'courier_name' => ['nullable', 'string', 'max:120'],
            'tracking_number' => ['nullable', 'string', 'max:120'],
            'tracking_url' => ['nullable', 'url', 'starts_with:https://', 'max:2048'],
            'estimated_delivery_date' => ['nullable', 'date_format:Y-m-d'],
            'note' => ['nullable', 'string', 'max:2000'],
        ]);
        $changed = DB::transaction(function () use ($business, $order, $data) {
            $locked = Order::where('business_id', $business->id)->whereKey($order->id)->lockForUpdate()->firstOrFail();
            if (! in_array($locked->status, ['confirmed', 'shipped'], true)) {
                throw ValidationException::withMessages(['stage' => 'Only confirmed or shipped orders can be updated.']);
            }
            $shipment = GrowyaOrderShipment::firstOrCreate(['order_id' => $locked->id], ['stage' => $locked->status === 'shipped' ? 'shipped' : 'confirmed']);
            $shipment = GrowyaOrderShipment::whereKey($shipment->id)->lockForUpdate()->firstOrFail();
            $current = $shipment->stage;
            $allowed = ['confirmed' => ['packed'], 'packed' => ['shipped'], 'shipped' => ['out_for_delivery', 'delivered'], 'out_for_delivery' => ['delivered']];
            // Confirmed order may have been shipped by legacy seller status action.
            if ($locked->status === 'shipped' && in_array($current, ['placed', 'confirmed', 'packed'], true)) {
                $current = 'shipped';
            }
            if (! in_array($data['stage'], $allowed[$current] ?? [], true)) {
                throw ValidationException::withMessages(['stage' => 'Invalid next stage. Refresh and try again.']);
            }
            if (in_array($data['stage'], ['shipped', 'out_for_delivery', 'delivered'], true) && empty($data['courier_name']) && empty($shipment->courier_name)) {
                throw ValidationException::withMessages(['courier_name' => 'Courier name is required for dispatch.']);
            }
            if (($data['status'] ?? $data['stage'] ?? null) === 'delivered') {
                app(DeliveryService::class)->assertCompletion($locked);
            }
            $shipment->update([
                'stage' => $data['stage'],
                'courier_name' => $data['courier_name'] ?? $shipment->courier_name,
                'tracking_number' => $data['tracking_number'] ?? $shipment->tracking_number,
                'tracking_url' => $data['tracking_url'] ?? $shipment->tracking_url,
                'estimated_delivery_date' => $data['estimated_delivery_date'] ?? $shipment->estimated_delivery_date,
            ]);
            if ($data['stage'] === 'shipped' && $locked->status === 'confirmed') {
                $locked->update(['status' => 'shipped']);
            }
            if ($data['stage'] === 'delivered' && $locked->status === 'shipped') {
                $locked->update(['status' => 'delivered']);
            }
            GrowyaOrderTrackingEvent::create(['order_id' => $locked->id, 'performed_by' => auth()->id(), 'stage' => $data['stage'], 'note' => $data['note'] ?? null]);

            return $data['stage'];
        }, 3);
        // Notifications are best-effort, never undo a successful tracking update.
        try {
            $recipient = Order::whereKey($order->id)->value('customer_id');
            if ($recipient) {
                app(GrowyaNotificationService::class)->send([(int) $recipient], 'order_delivery', 'Order delivery update', 'Order '.$order->order_number.' is now '.str_replace('_', ' ', $changed), '/my-orders/'.$order->id.'/tracking', $business->id);
            }
        } catch (\Throwable $e) {
            report($e);
        }

        return back()->with('success', 'Delivery stage updated.');
    }
}
