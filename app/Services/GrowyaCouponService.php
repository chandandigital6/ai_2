<?php

namespace App\Services;

use App\Models\{Business, GrowyaCoupon, GrowyaCouponRedemption};
use Illuminate\Validation\ValidationException;

class GrowyaCouponService
{
    /** Call only inside the checkout transaction, after validating cart lines. */
    public function prepare(Business $business, int $userId, ?string $code, int $subtotalCents): array
    {
        $code = strtoupper(trim((string) $code));
        if ($code === '') {
            return [null, 0];
        }

        $coupon = GrowyaCoupon::query()
            ->where('business_id', $business->id)
            ->where('code', $code)
            ->lockForUpdate()
            ->first();

        if (!$coupon || !$coupon->is_active) {
            $this->fail('Coupon is invalid or inactive.');
        }

        if ($coupon->starts_at && now()->lt($coupon->starts_at)) {
            $this->fail('Coupon is not active yet. It starts on ' . $coupon->starts_at->timezone(config('app.timezone'))->format('d M Y, h:i A') . '.');
        }

        if ($coupon->ends_at && now()->gt($coupon->ends_at)) {
            $this->fail('Coupon has expired.');
        }

        $minimumCents = (int) round((float) $coupon->minimum_subtotal * 100);
        if ($subtotalCents < $minimumCents) {
            $this->fail('Minimum order amount for this coupon is ₹' . number_format($minimumCents / 100, 2) . '.');
        }

        if ($coupon->usage_limit !== null && $coupon->used_count >= $coupon->usage_limit) {
            $this->fail('Coupon usage limit has been reached.');
        }

        $used = GrowyaCouponRedemption::where('coupon_id', $coupon->id)->where('user_id', $userId)->count();
        if ($used >= $coupon->per_user_limit) {
            $this->fail('You have already used this coupon the allowed number of times.');
        }

        $value = (int) round((float) $coupon->discount_value * 100);
        $discount = $coupon->discount_type === 'percent'
            ? (int) round($subtotalCents * (float) $coupon->discount_value / 100)
            : $value;

        // Maximum discount cap sirf percent coupons par lagta hai.
        if ($coupon->discount_type === 'percent' && $coupon->maximum_discount !== null) {
            $discount = min($discount, (int) round((float) $coupon->maximum_discount * 100));
        }

        return [$coupon, max(0, min($subtotalCents, $discount))];
    }

    public function record(GrowyaCoupon $coupon, int $userId, int $orderId, int $discountCents): void
    {
        GrowyaCouponRedemption::create([
            'coupon_id' => $coupon->id,
            'business_id' => $coupon->business_id,
            'user_id' => $userId,
            'order_id' => $orderId,
            'discount_amount' => number_format($discountCents / 100, 2, '.', ''),
        ]);

        $coupon->increment('used_count');
    }

    /**
     * Order cancel hone par coupon wapas free karo.
     * Call only inside the cancellation transaction.
     */
    public function release(int $orderId): void
    {
        $redemption = GrowyaCouponRedemption::where('order_id', $orderId)->lockForUpdate()->first();
        if (!$redemption) {
            return;
        }

        GrowyaCoupon::whereKey($redemption->coupon_id)
            ->where('used_count', '>', 0)
            ->lockForUpdate()
            ->decrement('used_count');

        $redemption->delete();
    }

    private function fail(string $message): never
    {
        throw ValidationException::withMessages(['coupon_code' => $message]);
    }
}
