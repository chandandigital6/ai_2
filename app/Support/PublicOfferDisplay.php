<?php

namespace App\Support;

use App\Models\Business;
use App\Models\Offer;
use App\Models\Product;
use App\Models\Service;
use Illuminate\Database\Eloquent\Builder;

class PublicOfferDisplay
{
    public static function visible(): Builder
    {
        $today = now()->toDateString();

        return Offer::query()
            ->where('status', 'active')
            ->where(function ($q) use ($today) {
                $q->whereNull('starts_at')->orWhereDate('starts_at', '<=', $today);
            })
            ->where(function ($q) use ($today) {
                $q->whereNull('ends_at')->orWhereDate('ends_at', '>=', $today);
            })
            ->whereHas('business', function ($q) {
                $q->where('status', 'active')
                  ->where('verification_status', 'approved');
            });
    }

    public static function forBusiness(Business $business, int $limit = 2)
    {
        if ($business->status !== 'active' || $business->verification_status !== 'approved') {
            return collect();
        }

        return self::visible()->where('business_id', $business->id)
            ->latest('id')->limit($limit)->get();
    }

    public static function forProduct(Product $product, int $limit = 4)
    {
        if ($product->status !== 'active') return collect();

        return self::visible()
            ->where('business_id', $product->business_id)
            ->whereHas('offer_products', fn($q) => $q->where('product_id', $product->id))
            ->latest('id')->limit($limit)->get();
    }

    public static function forService(Service $service, int $limit = 4)
    {
        if ($service->status !== 'active') return collect();

        return self::visible()
            ->where('business_id', $service->business_id)
            ->whereHas('offer_services', fn($q) => $q->where('service_id', $service->id))
            ->latest('id')->limit($limit)->get();
    }
}
