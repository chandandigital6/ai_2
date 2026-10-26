<?php

namespace App\Http\Controllers\Extensions;

use App\Models\Business;
use App\Models\Order;
use App\Services\Extensions\RazorpayService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class GatewayController extends ExtensionController
{
    public function create(Request $r, Order $order, RazorpayService $gateway)
    {
        abort_unless((int) $order->customer_id === (int) $r->user()->id, 404);
        $d = $r->validate(['request_key' => ['required', 'string', 'max:100']]);
        $row = $gateway->create($order, $d['request_key']);

        return $this->reply($r, ['id' => $row->id, 'key_id' => config('growya_extensions.razorpay.key'), 'provider_order_id' => $row->provider_order_id, 'amount_paise' => $row->amount_paise, 'currency' => $row->currency, 'status' => $row->status], 201);
    }

    public function verify(Request $r, Order $order, RazorpayService $gateway)
    {
        abort_unless((int) $order->customer_id === (int) $r->user()->id, 404);
        $gateway->enabled();
        $d = $r->validate(['razorpay_order_id' => ['required', 'string', 'max:100'], 'razorpay_payment_id' => ['required', 'string', 'max:100'], 'razorpay_signature' => ['required', 'string', 'size:64']]);
        $row = DB::table('growya_gateway_payments')->where('order_id', $order->id)->where('provider_order_id', $d['razorpay_order_id'])->first();
        abort_unless($row, 422);
        $expected = hash_hmac('sha256', $row->provider_order_id.'|'.$d['razorpay_payment_id'], config('growya_extensions.razorpay.secret'));
        abort_unless(hash_equals($expected, $d['razorpay_signature']), 422, 'Invalid payment signature.');

        return $this->reply($r, $gateway->reconcile($d['razorpay_payment_id'], $row->id));
    }

    public function webhook(Request $r, RazorpayService $gateway)
    {
        $secret = (string) config('growya_extensions.razorpay.webhook_secret');
        abort_unless($secret, 503);
        $sig = (string) $r->header('X-Razorpay-Signature');
        abort_unless(hash_equals(hash_hmac('sha256', $r->getContent(), $secret), $sig), 401);
        $payload = json_decode($r->getContent(), true, 32, JSON_THROW_ON_ERROR);
        if (($payload['event'] ?? null) !== 'payment.captured') {
            return response()->json(['received' => true, 'ignored' => true]);
        }
        $entity = $payload['payload']['payment']['entity'] ?? [];
        $gateway->reconcile($entity['id'] ?? '');

        return response()->json(['received' => true]);
    }

    public function index(Request $r, Business $business)
    {
        $this->business($r, $business, 'payments.view');
        $rows = DB::table('growya_gateway_payments as p')->join('orders as o', 'o.id', '=', 'p.order_id')->where('o.business_id', $business->id)->select('p.*', 'o.order_number')->orderByDesc('p.id')->paginate(25);

        return response()->json(['data' => $rows]);
    }

    public function reconcile(Request $r, Business $business, RazorpayService $gateway)
    {
        $this->business($r, $business, 'payments.manage');
        $d = $r->validate(['payment_id' => ['required', 'string', 'max:100'], 'gateway_payment_id' => ['required', 'integer']]);
        $row = DB::table('growya_gateway_payments as p')->join('orders as o', 'o.id', '=', 'p.order_id')->where('o.business_id', $business->id)->where('p.id', $d['gateway_payment_id'])->select('p.*')->first();
        abort_unless($row, 404);

        return $this->reply($r, $gateway->reconcile($d['payment_id'], $row->id));
    }
}