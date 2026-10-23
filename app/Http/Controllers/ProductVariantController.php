<?php

namespace App\Http\Controllers;

use App\Models\Business;
use App\Models\Product;
use App\Models\ProductStockMovement;
use App\Models\ProductVariant;
use App\Services\BusinessAccessService;
use App\Services\ProductOptionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class ProductVariantController extends Controller
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
            auth()->check() &&
                $this->access->can(
                    auth()->user(),
                    $business,
                    $permission
                ),
            403
        );
    }

    private function checkVariant(
        Product $product,
        ProductVariant $variant
    ): void {
        abort_unless(
            (int) $variant->product_id === (int) $product->id,
            404
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

        $variants = $product->variants()
            ->with('attributeValues.attribute')
            ->latest('id')
            ->paginate(20);

        $attributes = app(ProductOptionService::class)->forProduct($product);

        return view(
            'products.variants.index',
            compact('business', 'product', 'variants', 'attributes')
        );
    }

    public function store(
        Request $request,
        Business $business,
        Product $product
    ) {
        $this->authorizeAccess(
            $business,
            $product,
            'products.update'
        );

        $data = $this->validated($request, $product);

        $mapping = $request->has('attributes') ? ($data['attributes'] ?? []) : null;
        unset($data['attributes']);
        $image = $request->file('image');
        unset($data['image']);

        $path = null;

        try {
            if ($image) {
                $path = $image->store(
                    "businesses/{$business->id}/products/{$product->id}/variants",
                    'public'
                );

                if (! $path) {
                    throw new \RuntimeException(
                        'Variant image upload failed.'
                    );
                }

                $data['image'] = $path;
            }

            DB::transaction(function () use ($product, $data, $mapping) {
                $locked = Product::whereKey($product->id)->lockForUpdate()->firstOrFail();
                $ids = $mapping === null ? null : app(ProductOptionService::class)->variantValues($locked, $mapping);
                $variant = $locked->variants()->create($data);
                if ($ids !== null) {
                    $variant->attributeValues()->sync($ids);
                }
            });
        } catch (\Throwable $e) {
            if ($path) {
                Storage::disk('public')->delete($path);
            }

            throw $e;
        }

        return redirect()
            ->route('businesses.products.variants.index', [
                $business,
                $product,
            ])
            ->with('success', 'Variant added successfully.');
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
            'products.update'
        );

        $this->checkVariant($product, $variant);

        $data = $this->validated(
            $request,
            $product,
            $variant
        );

        $mapping = $request->has('attributes') ? ($data['attributes'] ?? []) : null;
        unset($data['attributes']);
        $image = $request->file('image');
        unset($data['image']);

        $oldImage = $variant->image;
        $newImage = null;

        try {
            if ($image) {
                $newImage = $image->store(
                    "businesses/{$business->id}/products/{$product->id}/variants",
                    'public'
                );

                if (! $newImage) {
                    throw new \RuntimeException(
                        'Variant image upload failed.'
                    );
                }

                $data['image'] = $newImage;
            }

            DB::transaction(function () use ($product, $variant, $data, $mapping, $business, $request) {
                $lockedProduct = Product::whereKey($product->id)->lockForUpdate()->firstOrFail();
                $lockedVariant = ProductVariant::whereKey($variant->id)->lockForUpdate()->firstOrFail();
                $ids = $mapping === null ? null : app(ProductOptionService::class)->variantValues($lockedProduct, $mapping, $lockedVariant);
                $before = (int) $lockedVariant->stock_quantity;
                if (array_key_exists('stock_quantity', $data) && (int) $data['stock_quantity'] !== $before) {
                    abort_unless($this->access->can($request->user(), $business, 'inventory.manage'), 403, 'Inventory permission is required to change existing stock.');
                    ProductStockMovement::create(['business_id' => $business->id, 'product_id' => $product->id, 'product_variant_id' => $variant->id, 'user_id' => $request->user()->id, 'movement_type' => 'adjustment', 'quantity_change' => (int) $data['stock_quantity'] - $before, 'quantity_before' => $before, 'quantity_after' => (int) $data['stock_quantity'], 'reason' => 'Variant stock adjustment']);
                }
                $lockedVariant->update($data);
                if ($ids !== null) {
                    $lockedVariant->attributeValues()->sync($ids);
                }
            });
        } catch (\Throwable $e) {
            if ($newImage) {
                Storage::disk('public')->delete($newImage);
            }

            throw $e;
        }

        if ($newImage && $oldImage) {
            Storage::disk('public')->delete($oldImage);
        }

        return redirect()
            ->route('businesses.products.variants.index', [
                $business,
                $product,
            ])
            ->with('success', 'Variant updated successfully.');
    }

    public function destroy(
        Business $business,
        Product $product,
        ProductVariant $variant
    ) {
        $this->authorizeAccess(
            $business,
            $product,
            'products.update'
        );

        $this->checkVariant($product, $variant);

        // Preserve historical references in orders/carts.
        // abort_if(
        //     $variant->order_items()->exists()
        //     || $variant->cart_items()->exists(),
        //     422,
        //     'Variant is used in orders or carts and cannot be deleted.'
        // );

        if (
            $variant->order_items()->exists()
            || $variant->cart_items()->exists()
        ) {
            return back()->withErrors([
                'variant' => 'This variant is used in an order or cart and cannot be deleted.',
            ]);
        }

        $path = $variant->image;

        DB::transaction(function () use ($variant) {
            $variant->delete();
        });

        if ($path) {
            Storage::disk('public')->delete($path);
        }

        return redirect()
            ->route('businesses.products.variants.index', [
                $business,
                $product,
            ])
            ->with('success', 'Variant deleted successfully.');
    }

    private function validated(
        Request $request,
        Product $product,
        ?ProductVariant $variant = null
    ): array {
        return $request->validate([
            'attributes' => ['sometimes', 'array', 'max:100'],
            'attributes.*' => ['nullable', 'integer', 'distinct'],
            'name' => [
                'required',
                'string',
                'max:255',
            ],
            'sku' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('product_variants', 'sku')
                    ->where(function ($query) use ($product) {
                        $query->where('product_id', $product->id);
                    })
                    ->ignore($variant?->id),
            ],
            'price' => [
            'required',
            'numeric',
            'min:0',
            'max:999999999999.99',
        ],
            'offer_price' => [
            'nullable',
            'numeric',
            'min:0',
            'lte:price',
        ],
            'stock_quantity' => [
            $variant ? 'sometimes' : 'required',
            'integer',
            'min:0',
            'max:2147483647',
        ],
            'status' => [
            'required',
            Rule::in(['active', 'inactive']),
        ],
            'image' => [
            'nullable',
            'image',
            'mimes:jpg,jpeg,png,webp',
            'max:3072',
        ],
        ]);
    }
}
