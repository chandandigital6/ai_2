<?php

namespace App\Services\Extensions;

use App\Models\Order;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class DeliveryService
{
    public function assertCompletion(Order $order): void
    {
        $policy = app(CommerceService::class)->policy($order->business_id);
        if (! $policy) {
            return;
        }
        $proof = DB::table('growya_delivery_proofs')->where('order_id', $order->id)->first();
        if ($policy->delivery_otp_required && (! $proof?->verified_at || now()->subMinutes(30)->gt($proof->verified_at))) {
            throw ValidationException::withMessages(['delivery' => 'Verify a fresh delivery OTP first.']);
        }
        if ($policy->delivery_proof_required && (! $proof?->file_path || ! $proof?->recipient_name)) {
            throw ValidationException::withMessages(['delivery' => 'A proof photo and recipient name are required.']);
        }
    }
}
