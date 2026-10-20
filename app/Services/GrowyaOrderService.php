<?php

namespace App\Services;

use App\Models\Business;
use App\Models\Cart;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductStockMovement;
use App\Models\ProductVariant;
use App\Services\Extensions\CommerceService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class GrowyaOrderService
{
    public function checkout(Business $business, Cart $cart, array $data): Order
    {
        return DB::transaction(function () use ($business, $cart, $data) {
            // Lock cart to prevent duplicate order submissions from parallel requests.
            $cart = Cart::query()->whereKey($cart->id)->lockForUpdate()->firstOrFail();
            if ($cart->status !== 'active' || (int) $cart->business_id !== (int) $business->id || (int) $cart->user_id !== (int) auth()->id()) {
                throw ValidationException::withMessages(['cart' => 'This cart is no longer available.']);
            }
            $items = $cart->cart_items()->orderBy('product_id')->orderBy('variant_id')->get();
            if ($items->isEmpty()) {
                throw ValidationException::withMessages(['cart' => 'Cart is empty.']);
            }
            $lines = [];
            $subtotalCents = 0;
            foreach ($items as $item) {
                $product = Product::query()->where('business_id', $business->id)->where('status', 'active')->whereKey($item->product_id)->lockForUpdate()->first();
                if (! $product) {
                    throw ValidationException::withMessages(['cart' => 'A product is no longer available.']);
                }
                $variant = null;
                if ($item->variant_id) {
                    $variant = ProductVariant::query()->where('product_id', $product->id)->where('status', 'active')->whereKey($item->variant_id)->lockForUpdate()->first();
                    if (! $variant) {
                        throw ValidationException::withMessages(['cart' => 'A product variant is no longer available.']);
                    }
                } elseif ($product->variants()->exists()) {
                    throw ValidationException::withMessages(['cart' => 'Please choose a variant for '.$product->name]);
                }
                $stock = $variant ?: $product;
                $qty = (int) $item->quantity;
                if ($qty < 1 || $qty > 100 || $stock->stock_quantity < $qty) {
                    throw ValidationException::withMessages(['cart' => 'Insufficient stock for '.$product->name]);
                }
                $rawPrice = $variant ? ($variant->offer_price ?? $variant->price) : ($product->offer_price ?? $product->price);
                $cents = (int) round((float) $rawPrice * 100);
                if ($cents < 0) {
                    throw ValidationException::withMessages(['cart' => 'Invalid product price.']);
                }
                $subtotalCents += $cents * $qty;
                $lines[] = compact('product', 'variant', 'stock', 'qty', 'cents');
            }
            // Coupon validated under row lock within the same checkout transaction.
            [$coupon, $discountCents] = app(GrowyaCouponService::class)->prepare($business, (int) auth()->id(), $data['coupon_code'] ?? null, $subtotalCents);
            $quote = app(CommerceService::class)->quote($business, $subtotalCents, $discountCents, $data['state']);
            if (isset($data['quoted_total_paise']) && (int) $data['quoted_total_paise'] !== $quote['total_paise']) {
                throw ValidationException::withMessages(['cart' => 'Prices or charges changed. Review the new total before placing your order.']);
            }
            $order = Order::create([
                'business_id' => $business->id,
                'customer_id' => auth()->id(),
                'order_number' => 'GY-'.Str::upper(Str::random(12)),
                'customer_name' => $data['customer_name'],
                'customer_phone' => $data['customer_phone'],
                'address_snapshot' => ['address' => $data['address'], 'city' => $data['city'], 'state' => $data['state'], 'pincode' => $data['pincode']],
                'subtotal' => number_format($subtotalCents / 100, 2, '.', ''),
                'discount_amount' => number_format($discountCents / 100, 2, '.', ''),
                'tax_amount' => number_format($quote['tax_paise'] / 100, 2, '.', ''),
                'shipping_amount' => number_format($quote['shipping_paise'] / 100, 2, '.', ''),
                'tax_snapshot' => $quote['snapshot'],
                'total_amount' => number_format($quote['total_paise'] / 100, 2, '.', ''),
                'payment_method' => $data['payment_method'],
                'payment_status' => 'pending',
                'status' => 'pending',
                'notes' => $data['notes'] ?? null,
            ]);
            foreach ($lines as $line) {
                ['product' => $product, 'variant' => $variant, 'stock' => $stock, 'qty' => $qty, 'cents' => $cents] = $line;
                $before = (int) $stock->stock_quantity;
                $after = $before - $qty;
                $stock->stock_quantity = $after;
                if (! $variant) {
                    $stock->stock_status = $after > 0 ? 'in_stock' : 'out_of_stock';
                }
                $stock->save();
                $order->items()->create([
                    'product_id' => $product->id,
                    'variant_id' => $variant?->id,
                    'product_name' => $product->name,
                    'sku' => $variant?->sku ?: $product->sku,
                    'quantity' => $qty,
                    'unit_price' => number_format($cents / 100, 2, '.', ''),
                    'total_price' => number_format($cents * $qty / 100, 2, '.', ''),
                    'product_snapshot' => ['slug' => $product->slug, 'variant_name' => $variant?->name],
                ]);
                ProductStockMovement::create([
                    'business_id' => $business->id,
                    'product_id' => $product->id,
                    'product_variant_id' => $variant?->id,
                    'user_id' => auth()->id(),
                    'movement_type' => 'stock_out',
                    'quantity_change' => -$qty,
                    'quantity_before' => $before,
                    'quantity_after' => $after,
                    'reason' => 'Order '.$order->order_number,
                ]);
                $product->increment('orders_count', $qty);
            }
            if ($coupon) {
                app(GrowyaCouponService::class)->record($coupon, (int) auth()->id(), $order->id, $discountCents);
            }
            $cart->update(['status' => 'converted']);

            return $order;
        }, 3);
    }

    public function cancel(Business $business, Order $order): void
    {
        DB::transaction(function () use ($business, $order) {
            // Lock business first to make cancellation/repeated status actions consistent.
            $order = Order::query()->where('business_id', $business->id)->whereKey($order->id)->lockForUpdate()->firstOrFail();
            if ($order->status === 'cancelled') {
                return;
            }
            if (DB::table('growya_gateway_payments')->where('order_id', $order->id)->whereIn('status', ['creating', 'requesting', 'created', 'unknown', 'paid', 'captured_review'])->exists()) {
                throw ValidationException::withMessages(['status' => 'Reconcile the gateway payment before cancellation.']);
            }
            if (! in_array($order->status, ['pending', 'confirmed'], true)) {
                throw ValidationException::withMessages(['status' => 'Only pending or confirmed orders can be cancelled.']);
            }
            if ($order->payment_status === 'paid') {
                throw ValidationException::withMessages(['status' => 'Refund paid orders before cancellation.']);
            }
            foreach ($order->items()->orderBy('product_id')->orderBy('variant_id')->get() as $item) {
                $product = Product::withTrashed()->whereKey($item->product_id)->lockForUpdate()->first();
                if (! $product) {
                    throw ValidationException::withMessages(['status' => 'Product no longer exists: manual stock adjustment required.']);
                }
                $variant = $item->variant_id ? ProductVariant::whereKey($item->variant_id)->lockForUpdate()->first() : null;
                if ($item->variant_id && ! $variant) {
                    throw ValidationException::withMessages(['status' => 'Variant missing: manual stock adjustment required.']);
                }
                $stock = $variant ?: $product;
                $before = (int) $stock->stock_quantity;
                $qty = (int) $item->quantity;
                $after = $before + $qty;
                if ($after > 2147483647) {
                    throw ValidationException::withMessages(['status' => 'Stock overflow.']);
                }
                $stock->stock_quantity = $after;
                if (! $variant) {
                    $stock->stock_status = 'in_stock';
                }
                $stock->save();
                ProductStockMovement::create([
                    'business_id' => $business->id,
                    'product_id' => $product->id,
                    'product_variant_id' => $variant?->id,
                    'user_id' => auth()->id(),
                    'movement_type' => 'stock_in',
                    'quantity_change' => $qty,
                    'quantity_before' => $before,
                    'quantity_after' => $after,
                    'reason' => 'Cancelled order '.$order->order_number,
                ]);
                $product->decrement('orders_count', min($qty, (int) $product->orders_count));
            }
            // Cancelled order ka coupon wapas free karo (used_count aur per-user limit dono).
            app(GrowyaCouponService::class)->release($order->id);
            $order->update(['status' => 'cancelled']);
        }, 3);
    }
}
