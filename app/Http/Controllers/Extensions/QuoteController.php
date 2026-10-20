<?php

namespace App\Http\Controllers\Extensions;

use App\Models\Business;
use App\Models\Cart;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\UserAddress;
use App\Services\Extensions\CommerceService;
use App\Services\Extensions\RazorpayService;
use App\Services\GrowyaCouponService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class QuoteController extends ExtensionController
{
    public function quote(Request $r, Business $business, CommerceService $commerce)
    {
        abort_unless($business->status === 'active' && $business->verification_status === 'approved', 404);
        $d = $r->validate(['address_id' => ['nullable', 'integer', Rule::exists('user_addresses', 'id')->where('user_id', $r->user()->id)], 'state' => ['required_without:address_id', 'nullable', 'string', 'max:100'], 'coupon_code' => ['nullable', 'string', 'max:40']]);
        $state = ! empty($d['address_id']) ? UserAddress::with('state')->findOrFail($d['address_id'])->state?->name : $d['state'];
        abort_unless($state, 422, 'Valid destination state required.');
        $result = DB::transaction(function () use ($r, $business, $d, $state, $commerce) {
            $cart = Cart::where('business_id', $business->id)->where('user_id', $r->user()->id)->where('status', 'active')->lockForUpdate()->first();
            abort_unless($cart, 422, 'Cart is empty.');
            $subtotal = 0;
            $items = $cart->cart_items()->get();
            abort_if($items->isEmpty(), 422, 'Cart is empty.');
            foreach ($items as $item) {
                $p = Product::where('business_id', $business->id)->where('status', 'active')->find($item->product_id);
                if (! $p) {
                    throw ValidationException::withMessages(['cart' => 'A product is unavailable.']);
                }$variant = $item->variant_id ? ProductVariant::where('product_id', $p->id)->where('status', 'active')->find($item->variant_id) : null;
                abort_if($item->variant_id && ! $variant, 422, 'Variant unavailable.');
                $stock = $variant ?: $p;
                abort_if($stock->stock_quantity < $item->quantity, 422, 'Insufficient stock.');
                $price = $variant ? ($variant->offer_price ?? $variant->price) : ($p->offer_price ?? $p->price);
                $subtotal += RazorpayService::paise((string) $price) * (int) $item->quantity;
            }
            [, $discount] = app(GrowyaCouponService::class)->prepare($business, $r->user()->id, $d['coupon_code'] ?? null, $subtotal);
            $q = $commerce->quote($business, $subtotal, $discount, $state);
            $q['subtotal_paise'] = $subtotal;
            $q['discount_paise'] = $discount;

            return $q;
        }, 3);

        return response()->json(['data' => $result]);
    }
}
