<?php

namespace App\Http\Controllers;

use App\Models\Attribute;
use App\Models\AttributeValue;
use App\Models\Business;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Services\BusinessAccessService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

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
                }
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

        $submitted = collect($data['attributes'] ?? [])
            ->filter(fn ($id) => $id !== null && $id !== '');

        $allowed = $product->attributeValues()
            ->get([
                'attribute_values.id',
                'attribute_values.attribute_id'
            ])
            ->keyBy('id');

        $selectedIds = [];

        foreach ($submitted as $attributeId => $valueId) {
            $value = $allowed->get((int) $valueId);

            if (
                !$value
                || (int) $value->attribute_id !== (int) $attributeId
            ) {
                throw ValidationException::withMessages([
                    'attributes' =>
                        'Selected attribute value does not belong to this product or attribute.',
                ]);
            }

            $selectedIds[] = (int) $valueId;
        }

        sort($selectedIds);

        // Prevent identical attribute combinations in one product.
        // Empty mappings are allowed for legacy/default variants.
        if (count($selectedIds) > 0) {
            $duplicate = $product->variants()
                ->where('id', '!=', $variant->id)
                ->with('attributeValues:id')
                ->get()
                ->contains(function ($other) use ($selectedIds) {
                    $otherIds = $other->attributeValues
                        ->pluck('id')
                        ->map(fn ($id) => (int) $id)
                        ->sort()
                        ->values()
                        ->all();

                    return $otherIds === $selectedIds;
                });

            if ($duplicate) {
                throw ValidationException::withMessages([
                    'attributes' =>
                        'Another variant already has this attribute combination.',
                ]);
            }
        }

        DB::transaction(function () use ($variant, $selectedIds) {
            $variant->attributeValues()->sync($selectedIds);
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
