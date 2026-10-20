<?php

namespace App\Services\Extensions;

use App\Models\Business;
use Illuminate\Support\Facades\DB;

class CommerceService
{
    public function policy(int $id): ?object
    {
        return DB::table('growya_commerce_policies')->where('business_id', $id)->first();
    }

    public function quote(Business $business, int $subtotal, int $discount, string $buyerState): array
    {
        $p = $this->policy($business->id);
        $base = max(0, $subtotal - $discount);
        $shipping = $p?->shipping_enabled ? (($p->free_above_paise !== null && $base >= (int) $p->free_above_paise) ? 0 : (int) $p->shipping_paise) : 0;
        $rate = $p?->tax_enabled ? (int) $p->tax_basis_points : 0;
        $taxable = $base + $shipping;
        $tax = intdiv($taxable * $rate + 5000, 10000);
        $intra = $p?->tax_enabled && mb_strtolower(trim($p->seller_state ?? '')) === mb_strtolower(trim($buyerState));

        return ['shipping_paise' => $shipping, 'tax_paise' => $tax, 'total_paise' => $taxable + $tax, 'snapshot' => ['mode' => 'exclusive', 'taxable_paise' => $taxable, 'rate_basis_points' => $rate, 'seller_state' => $p?->seller_state, 'buyer_state' => $buyerState, 'gst_number' => $p?->tax_enabled ? $business->gst_number : null, 'cgst_paise' => $intra ? intdiv($tax, 2) : 0, 'sgst_paise' => $intra ? $tax - intdiv($tax, 2) : 0, 'igst_paise' => $intra ? 0 : $tax]];
    }
}
