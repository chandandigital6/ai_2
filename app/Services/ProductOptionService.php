<?php

namespace App\Services;

use App\Models\Attribute;
use App\Models\Business;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ProductOptionService
{
    public function catalog(Business $business)
    {
        return Attribute::query()
            ->where(fn ($q) => $q->whereNull('business_id')->orWhere('business_id', $business->id))
            ->whereHas('values')->with(['values' => fn ($q) => $q->orderBy('sort_order')->orderBy('id')])
            ->orderBy('name')->get();
    }

    public function forProduct(Product $product)
    {
        $ids = $product->attributeValues()->pluck('attribute_values.id');

        return Attribute::query()
            ->where(fn ($q) => $q->whereNull('business_id')->orWhere('business_id', $product->business_id))
            ->whereHas('values', fn ($q) => $q->whereIn('id', $ids))
            ->with(['values' => fn ($q) => $q->whereIn('id', $ids)->orderBy('sort_order')->orderBy('id')])
            ->orderBy('name')->get();
    }

    // Call under the parent product lock, shared by all option/variant writes.
    public function syncProduct(Product $product, array $ids): void
    {
        $ids = array_values(array_unique(array_map('intval', $ids)));
        $used = DB::table('product_variant_attribute_values as pva')
            ->join('product_variants as v', 'v.id', '=', 'pva.product_variant_id')
            ->where('v.product_id', $product->id)->pluck('pva.attribute_value_id')->all();
        if (array_diff($used, $ids)) {
            throw ValidationException::withMessages(['attribute_values' => 'Some values are used by existing variants. Change those variants first, then remove the values.']);
        }
        $product->attributeValues()->sync($ids);
    }

    public function variantValues(Product $product, array $mapping, ?ProductVariant $variant = null): array
    {
        $allowed = $product->attributeValues()
            ->whereHas('attribute', fn ($q) => $q->whereNull('business_id')->orWhere('business_id', $product->business_id))
            ->get(['attribute_values.id', 'attribute_values.attribute_id'])->keyBy('id');
        $ids = [];
        foreach ($mapping as $attributeId => $valueId) {
            if ($valueId === null || $valueId === '') {
                continue;
            }
            $value = $allowed->get((int) $valueId);
            if (! $value || (string) $value->attribute_id !== (string) $attributeId) {
                throw ValidationException::withMessages(['attributes' => 'Choose only saved values belonging to this product and the correct attribute.']);
            }
            $ids[] = (int) $valueId;
        }
        sort($ids);
        if ($ids) {
            $duplicate = $product->variants()->when($variant, fn ($q) => $q->where('id', '!=', $variant->id))
                ->with('attributeValues:id')->get()->contains(fn ($v) => $v->attributeValues->pluck('id')->map(fn ($id) => (int) $id)->sort()->values()->all() === $ids);
            if ($duplicate) {
                throw ValidationException::withMessages(['attributes' => 'This combination already exists. Edit the existing variant instead.']);
            }
        }

        return $ids;
    }
}
