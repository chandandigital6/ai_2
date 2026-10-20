<?php

namespace App\Services\Extensions;

use App\Models\Order;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;

class RazorpayService
{
    public function enabled(): void
    {
        abort_unless(config('growya_extensions.razorpay.enabled') && config('growya_extensions.razorpay.key') && config('growya_extensions.razorpay.secret'), 503, 'Razorpay has not been configured.');
    }

    private function http()
    {
        $this->enabled();

        return Http::withBasicAuth(config('growya_extensions.razorpay.key'), config('growya_extensions.razorpay.secret'))->acceptJson()->timeout(20)->withOptions(['allow_redirects' => false]);
    }

    public static function paise(string $amount): int
    {
        if (! preg_match('/^([0-9]{1,12})(?:\.([0-9]{1,2}))?$/', $amount, $m)) {
            throw new \InvalidArgumentException('Invalid money amount.');
        }

        return (int) $m[1] * 100 + (int) str_pad($m[2] ?? '', 2, '0');
    }

    public function create(Order $order, string $key): object
    {
        $this->enabled();
        $row = DB::transaction(function () use ($order, $key) {
            $order = Order::whereKey($order->id)->lockForUpdate()->firstOrFail();
            abort_unless($order->payment_method === 'razorpay' && $order->payment_status === 'pending' && in_array($order->status, ['pending', 'confirmed'], true), 422, 'Order not eligible for gateway payment.');
            $old = DB::table('growya_gateway_payments')->where('order_id', $order->id)->latest('id')->first();
            if ($old) {
                abort_unless($old->request_key === $key, 409, 'An existing payment attempt must be reconciled first.');

                return $old;
            }
            $amount = self::paise((string) $order->total_amount);
            abort_unless($amount >= 100, 422, 'Gateway amount must be at least INR 1.');
            $id = DB::table('growya_gateway_payments')->insertGetId(['order_id' => $order->id, 'request_key' => $key, 'amount_paise' => $amount, 'currency' => 'INR', 'status' => 'creating', 'created_at' => now(), 'updated_at' => now()]);

            return DB::table('growya_gateway_payments')->find($id);
        }, 3);
        // Only the request that reserved the row may call the provider. A retry of an ambiguous create never duplicates it.
        if ($row->status !== 'creating' || $row->provider_order_id) {
            return $row;
        }
        $claimed = DB::table('growya_gateway_payments')->where('id', $row->id)->where('status', 'creating')->update(['status' => 'requesting', 'updated_at' => now()]);
        if (! $claimed) {
            return DB::table('growya_gateway_payments')->find($row->id);
        }
        try {
            $r = $this->http()->post('https://api.razorpay.com/v1/orders', ['amount' => $row->amount_paise, 'currency' => 'INR', 'receipt' => 'growya-'.$row->id, 'notes' => ['growya_order_id' => (string) $order->id]]);
            if (! $r->successful() || ! $r->json('id')) {
                DB::table('growya_gateway_payments')->where('id', $row->id)->update(['status' => $r->status() < 500 ? 'failed' : 'unknown', 'updated_at' => now()]);
                abort(502, 'Gateway order could not be created. Reconcile before retrying.');
            }
            DB::table('growya_gateway_payments')->where('id', $row->id)->update(['provider_order_id' => $r->json('id'), 'status' => 'created', 'updated_at' => now()]);
        } catch (ConnectionException $e) {
            DB::table('growya_gateway_payments')->where('id', $row->id)->update(['status' => 'unknown', 'updated_at' => now()]);
            abort(502, 'Gateway outcome unknown; reconcile before retrying.');
        }

        return DB::table('growya_gateway_payments')->find($row->id);
    }

    public function reconcile(string $paymentId, ?int $expectedRowId = null): array
    {
        abort_unless(preg_match('/^pay_[A-Za-z0-9]+$/', $paymentId), 422);
        $r = $this->http()->get('https://api.razorpay.com/v1/payments/'.rawurlencode($paymentId));
        abort_unless($r->successful(), 502, 'Could not verify payment.');
        $p = $r->json();
        $candidate = DB::table('growya_gateway_payments')->where('provider_order_id', $p['order_id'] ?? '')->first();
        if (! $candidate && preg_match('/^order_[A-Za-z0-9]+$/', (string) ($p['order_id'] ?? ''))) {
            $remote = $this->http()->get('https://api.razorpay.com/v1/orders/'.rawurlencode($p['order_id']));
            abort_unless($remote->successful(), 502, 'Could not reconcile gateway order.');
            $receipt = (string) $remote->json('receipt', '');
            if (preg_match('/^growya-([0-9]+)$/', $receipt, $match)) {
                $reserved = DB::table('growya_gateway_payments')->find((int) $match[1]);
                if ($reserved && ! $reserved->provider_order_id && in_array($reserved->status, ['creating', 'requesting', 'unknown'], true) && (int) $remote->json('amount') === (int) $reserved->amount_paise && $remote->json('currency') === 'INR') {
                    DB::table('growya_gateway_payments')->where('id', $reserved->id)->whereNull('provider_order_id')->update(['provider_order_id' => $p['order_id'], 'status' => 'created', 'updated_at' => now()]);
                    $candidate = DB::table('growya_gateway_payments')->find($reserved->id);
                }
            }
        }
        if (! $candidate && ! $expectedRowId) {
            return ['ignored' => true];
        }
        abort_unless($candidate && (! $expectedRowId || (int) $candidate->id === $expectedRowId), 422, 'Payment is for another order.');
        abort_unless(($p['status'] ?? null) === 'captured' && ($p['currency'] ?? null) === 'INR' && (int) ($p['amount'] ?? 0) === (int) $candidate->amount_paise, 422, 'A captured payment for the exact amount is required.');

        return DB::transaction(function () use ($candidate, $paymentId) {
            $order = Order::whereKey($candidate->order_id)->lockForUpdate()->firstOrFail();
            $row = DB::table('growya_gateway_payments')->where('id', $candidate->id)->lockForUpdate()->first();
            if ($row->status === 'paid') {
                return ['paid' => true, 'order_id' => $order->id];
            }
            if ($row->provider_payment_id && $row->provider_payment_id !== $paymentId) {
                throw ValidationException::withMessages(['payment' => 'A different payment is already linked.']);
            }
            abort_unless(self::paise((string) $order->total_amount) === (int) $row->amount_paise, 409, 'Order amount changed; manual reconciliation required.');
            $review = $order->status === 'cancelled' || $order->payment_status !== 'pending';
            DB::table('growya_gateway_payments')->where('id', $row->id)->update(['provider_payment_id' => $paymentId, 'status' => $review ? 'captured_review' : 'paid', 'updated_at' => now()]);
            if (! $review) {
                $order->update(['payment_status' => 'paid']);
            }

            return ['paid' => ! $review, 'requires_review' => $review, 'order_id' => $order->id];
        }, 3);
    }
}
