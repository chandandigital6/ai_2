<?php

namespace App\Services;

use App\Models\GrowyaPaidPromotion;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class GrowyaPaidPromotionService
{
    private function eligible(string $type): Builder
    {
        $q = GrowyaPaidPromotion::query()->live()
            ->where('payment_status', 'verified')
            ->whereHas('business', fn ($q) => $q->where('status', 'active')->where('verification_status', 'approved'))
            ->whereHas('package', fn ($q) => $q->where('target_type', $type));
        if ($type === 'product') {
            $q->whereHas('product', fn ($q) => $q->where('status', 'active')
                ->whereColumn('products.business_id', 'growya_paid_promotions.business_id'));
        } else {
            $q->whereNull('product_id');
        }

        return $q;
    }

    // Correlated EXISTS avoids duplicate rows and unbounded IN lists. Apply before limit/paginate.
    public function prioritize(Builder $query, string $type): Builder
    {
        $table = $query->getModel()->getTable();
        if ($query->getQuery()->columns === null) {
            $query->select($table.'.*');
        }
        $sub = $this->eligible($type)->whereColumn('growya_paid_promotions.'.($type === 'product' ? 'product_id' : 'business_id'), $table.'.id')->selectRaw('1');

        return $query->selectRaw('CASE WHEN EXISTS ('.$sub->toSql().') THEN 1 ELSE 0 END AS is_sponsored', $sub->getBindings())
            ->orderByDesc('is_sponsored');
    }

    public function businessIds(): array
    {
        return $this->eligible('business')->distinct()->pluck('business_id')->map(fn ($id) => (int) $id)->all();
    }

    public function productIds(): array
    {
        return $this->eligible('product')->distinct()->pluck('product_id')->map(fn ($id) => (int) $id)->all();
    }

    public function forBusinesses($ids)
    {
        return $this->eligible('business')->select(['id', 'business_id', 'product_id'])->whereIn('business_id', $ids)->latest('id')->get()->unique('business_id')->keyBy('business_id');
    }

    public function forProducts($ids)
    {
        return $this->eligible('product')->select(['id', 'business_id', 'product_id'])->whereIn('product_id', $ids)->latest('id')->get()->unique('product_id')->keyBy('product_id');
    }

    public function countImpressions(array $businessIds = [], array $productIds = []): void
    {
        foreach (['business' => $businessIds, 'product' => $productIds] as $type => $ids) {
            if ($ids) {
                $shown = $type === 'business' ? $this->forBusinesses($ids) : $this->forProducts($ids);
                $this->eligible($type)->whereIn('id', $shown->pluck('id'))->update(['impressions' => DB::raw('impressions + 1')]);
            }
        }
    }
}
