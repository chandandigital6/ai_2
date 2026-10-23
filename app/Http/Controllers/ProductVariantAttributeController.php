<?php

namespace App\Http\Controllers;

use App\Models\Attribute;
use App\Models\Business;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Services\BusinessAccessService;
use App\Services\ProductOptionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProductVariantAttributeController extends Controller
{
    public function __construct(
        private BusinessAccessService $access
    ) {}

    private function authorizeAccess(
        Business $business,
        Product $product,
        ProductVariant $variant,
        string $permission
    ): void {
        // Check both business and product ownership.
        abort_unless(
            (int) $product->business_id === (int) $business->id,
            404
        );

        abort_unless(
            (int) $variant->product_id === (int) $product->id,
            404
        );

        abort_unless(
            auth()->check()
            && $this->access->can(
                auth()->user(),
                $business,
                $permission
            ),
            403
        );
    }

    private function attributes(Product $product)
    {
        $allowedIds = $product->attributeValues()
            ->pluck('attribute_values.id');

        return Attribute::query()
            ->whereHas('values', function ($query) use ($allowedIds) {
                $query->whereIn('id', $allowedIds);
            })
            ->with([
                'values' => function ($query) use ($allowedIds) {
                    $query->whereIn('id', $allowedIds)
                        ->orderBy('sort_order')
                        ->orderBy('id');
                },
            ])
            ->orderBy('name')
            ->get();
    }

    public function edit(
        Business $business,
        Product $product,
        ProductVariant $variant
    ) {
        $this->authorizeAccess(
            $business,
            $product,
            $variant,
            'products.view'
        );

        $attributes = $this->attributes($product);

        $selected = $variant->attributeValues()
            ->get(['attribute_values.id', 'attribute_values.attribute_id'])
            ->pluck('id', 'attribute_id')
            ->map(fn ($id) => (int) $id)
            ->all();

        return view(
            'products.variants.attributes',
            compact(
                'business',
                'product',
                'variant',
                'attributes',
                'selected'
            )
        );
    }

    public function update(
        Request $request,
        Business $business,
        Product $product,
        ProductVariant $variant
    ) {
        $this->authorizeAccess(
            $business,
            $product,
            $variant,
            'products.update'
        );

        $data = $request->validate([
            'attributes' => ['sometimes', 'array'],
            'attributes.*' => [
                'nullable',
                'integer',
                'distinct',
            ],
        ]);

        DB::transaction(function () use ($product, $variant, $data) {
            $locked = Product::whereKey($product->id)->lockForUpdate()->firstOrFail();
            $ids = app(ProductOptionService::class)->variantValues($locked, $data['attributes'] ?? [], $variant);
            $variant->attributeValues()->sync($ids);
        });

        return redirect()
            ->route(
                'businesses.products.variants.attributes.edit',
                [$business, $product, $variant]
            )
            ->with(
                'success',
                'Variant attributes updated successfully.'
            );
    }
}
