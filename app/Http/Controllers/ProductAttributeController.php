<?php

namespace App\Http\Controllers;

use App\Models\Attribute;
use App\Models\Business;
use App\Models\Product;
use App\Services\BusinessAccessService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class ProductAttributeController extends Controller
{
    public function __construct(
        private BusinessAccessService $access
    ) {}

    private function authorizeAccess(
        Business $business,
        Product $product,
        string $permission
    ): void {
        abort_unless(
            (int) $product->business_id === (int) $business->id,
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

    public function index(
        Business $business,
        Product $product
    ) {
        $this->authorizeAccess(
            $business,
            $product,
            'products.view'
        );

        $attributes = Attribute::query()->where(fn ($q) => $q->whereNull('business_id')->orWhere('business_id', $business->id))
            ->whereHas('values')
            ->with([
                'values' => fn ($query) => $query
                    ->orderBy('sort_order')
                    ->orderBy('id'),
            ])
            ->orderBy('name')
            ->get();

        $selectedValues = $product->attributeValues()
            ->pluck('attribute_values.id')
            ->map(fn ($id) => (int) $id)
            ->all();

        return view(
            'products.attributes',
            compact(
                'business',
                'product',
                'attributes',
                'selectedValues'
            )
        );
    }

    public function update(
        Request $request,
        Business $business,
        Product $product
    ) {
        $this->authorizeAccess(
            $business,
            $product,
            'products.update'
        );

        $data = $request->validate([
            'values' => ['sometimes', 'array'],
            'values.*' => [
                'required',
                'integer',
                'distinct',
                Rule::exists('attribute_values', 'id')->whereIn('attribute_id', Attribute::where(fn ($q) => $q->whereNull('business_id')->orWhere('business_id', $business->id))->select('id')),
            ],
        ]);

        DB::transaction(function () use ($product, $data) {
            $product->attributeValues()->sync(
                $data['values'] ?? []
            );
        });

        return redirect()
            ->route(
                'businesses.products.attributes.index',
                [$business, $product]
            )
            ->with(
                'success',
                'Product attributes updated successfully.'
            );
    }
}
