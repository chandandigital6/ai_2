<?php

namespace App\Http\Controllers\Extensions;

use App\Models\Business;
use App\Models\GrowyaOrderShipment;
use App\Models\GrowyaOrderTrackingEvent;
use App\Models\Order;
use App\Services\BusinessAccessService;
use App\Services\Extensions\DeliveryService;
use App\Services\Extensions\SmsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class DeliveryProofController extends ExtensionController
{
    private function scope(Request $r, Business $b, Order $o): void
    {
        $this->business($r, $b, 'delivery.manage');
        abort_unless((int) $o->business_id === (int) $b->id, 404);
        abort_unless($o->status === 'shipped', 422, 'Only shipped orders can be confirmed.');
    }

    public function send(Request $r, Business $business, Order $order, SmsService $sms)
    {
        $this->scope($r, $business, $order);
        abort_unless($sms->available(), 503, 'SMS provider missing.');
        abort_unless(preg_match('/^[6-9][0-9]{9}$/', (string) $order->customer_phone), 422, 'A valid customer phone is required.');
        $code = (string) random_int(100000, 999999);
        DB::transaction(function () use ($order, $code) {
            Order::whereKey($order->id)->lockForUpdate()->firstOrFail();
            $old = DB::table('growya_delivery_proofs')->where('order_id', $order->id)->first();
            abort_if($old?->sent_at && now()->subSeconds(60)->lt($old->sent_at), 429, 'Wait 60 seconds.');
            DB::table('growya_delivery_proofs')->updateOrInsert(['order_id' => $order->id], ['otp_hash' => Hash::make($code), 'expires_at' => now()->addMinutes(5), 'sent_at' => now(), 'attempts' => 0, 'verified_at' => null, 'updated_at' => now(), 'created_at' => now()]);
        }, 3);
        try {
            $sms->send($order->customer_phone, $code, 'delivery');
        } catch (\Throwable $e) {
            DB::table('growya_delivery_proofs')->where('order_id', $order->id)->update(['expires_at' => now()]);
            throw $e;
        }

        return $this->reply($r, ['sent' => true, 'expires_in' => 300]);
    }

    public function verify(Request $r, Business $business, Order $order)
    {
        $this->scope($r, $business, $order);
        $d = $r->validate(['code' => ['required', 'digits:6']]);
        $ok = DB::transaction(function () use ($order, $d, $r) {
            $p = DB::table('growya_delivery_proofs')->where('order_id', $order->id)->lockForUpdate()->first();
            if (! $p || ! $p->otp_hash || $p->verified_at || ! $p->expires_at || now()->gte($p->expires_at) || $p->attempts >= 5) {
                return false;
            }DB::table('growya_delivery_proofs')->where('id', $p->id)->increment('attempts');
            if (! Hash::check($d['code'], $p->otp_hash)) {
                return false;
            }DB::table('growya_delivery_proofs')->where('id', $p->id)->update(['verified_at' => now(), 'performed_by' => $r->user()->id, 'updated_at' => now()]);

            return true;
        }, 3);
        if (! $ok) {
            throw ValidationException::withMessages(['code' => 'Code invalid, expired, used or attempt limit reached.']);
        }

        return $this->reply($r, ['verified' => true]);
    }

    public function upload(Request $r, Business $business, Order $order)
    {
        $this->scope($r, $business, $order);
        $d = $r->validate(['proof' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'], 'recipient_name' => ['required', 'string', 'max:120']]);
        $path = $r->file('proof')->store('delivery-proofs', 'local');
        abort_unless($path, 500);
        $oldPath = null;
        try {
            DB::transaction(function () use ($r, $order, $d, $path, &$oldPath) {
                $o = Order::whereKey($order->id)->lockForUpdate()->firstOrFail();
                abort_unless($o->status === 'shipped', 422);
                $old = DB::table('growya_delivery_proofs')->where('order_id', $o->id)->first();
                $oldPath = $old?->file_path;
                DB::table('growya_delivery_proofs')->updateOrInsert(['order_id' => $o->id], ['file_path' => $path, 'recipient_name' => $d['recipient_name'], 'performed_by' => $r->user()->id, 'created_at' => now(), 'updated_at' => now()]);
            });
        } catch (\Throwable $e) {
            Storage::disk('local')->delete($path);
            throw $e;
        }if ($oldPath) {
            Storage::disk('local')->delete($oldPath);
        }

        return $this->reply($r, ['uploaded' => true], 201);
    }

    public function complete(Request $r, Business $business, Order $order, DeliveryService $delivery)
    {
        $this->business($r, $business, 'delivery.manage');
        abort_unless((int) $order->business_id === (int) $business->id, 404);
        DB::transaction(function () use ($r, $business, $order, $delivery) {
            $o = Order::where('business_id', $business->id)->whereKey($order->id)->lockForUpdate()->firstOrFail();
            if ($o->status === 'delivered') {
                return;
            }abort_unless($o->status === 'shipped', 422);
            $delivery->assertCompletion($o);
            $o->update(['status' => 'delivered']);
            $s = GrowyaOrderShipment::firstOrCreate(['order_id' => $o->id], ['stage' => 'shipped']);
            $s->update(['stage' => 'delivered']);
            GrowyaOrderTrackingEvent::create(['order_id' => $o->id, 'performed_by' => $r->user()->id, 'stage' => 'delivered', 'note' => 'Delivery confirmation completed.']);
        }, 3);

        return $this->reply($r, ['delivered' => true]);
    }

    public function file(Request $r, Order $order)
    {
        $customer = (int) $order->customer_id === (int) $r->user()->id;
        $business = Business::findOrFail($order->business_id);
        abort_unless($customer || app(BusinessAccessService::class)->can($r->user(), $business, 'delivery.view'), 403);
        $proof = DB::table('growya_delivery_proofs')->where('order_id', $order->id)->first();
        abort_unless($proof?->file_path && Storage::disk('local')->exists($proof->file_path), 404);

        return Storage::disk('local')->response($proof->file_path, null, ['Cache-Control' => 'private, no-store']);
    }
}
