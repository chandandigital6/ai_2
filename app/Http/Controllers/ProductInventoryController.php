<?php

namespace App\Http\Controllers;

use App\Models\Business;
use App\Models\Product;
use App\Models\ProductStockMovement;
use App\Models\ProductVariant;
use App\Services\BusinessAccessService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ProductInventoryController extends Controller
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

    public function index(Business $business, Product $product)
    {
        $this->authorizeAccess(
            $business,
            $product,
            'inventory.view'
        );

        $variants = $product->variants()
            ->orderBy('name')
            ->get();

        $movements = ProductStockMovement::query()
            ->where('business_id', $business->id)
            ->where('product_id', $product->id)
            ->with([
                'user:id,name',
                'variant:id,name',
            ])
            ->latest('id')
            ->paginate(20);

        $lowStockLimit = 5;

        return view('products.inventory.index', compact(
            'business',
            'product',
            'variants',
            'movements',
            'lowStockLimit'
        ));
    }

    public function store(
        Request $request,
        Business $business,
        Product $product
    ) {
        $this->authorizeAccess(
            $business,
            $product,
            'inventory.manage'
        );

        $data = $request->validate([
            'stock_target' => [
                'required',
                Rule::in(['product', 'variant']),
            ],

            'variant_id' => [
                'nullable',
                'integer',
                Rule::exists('product_variants', 'id')
                    ->where('product_id', $product->id),
            ],

            'movement_type' => [
                'required',
                Rule::in([
                    'stock_in',
                    'stock_out',
                    'adjustment',
                ]),
            ],

            'quantity' => [
                'required',
                'integer',
                'min:0',
                'max:2147483647',
            ],

            'reason' => [
                'required',
                'string',
                'max:255',
            ],
        ]);

        $isVariant = $data['stock_target'] === 'variant';

        if ($isVariant && empty($data['variant_id'])) {
            throw ValidationException::withMessages([
                'variant_id' => 'Please select a product variant.',
            ]);
        }

        if (! $isVariant && ! empty($data['variant_id'])) {
            throw ValidationException::withMessages([
                'variant_id' => 'Variant must not be selected for main product stock.',
            ]);
        }

        if (
            $data['movement_type'] !== 'adjustment'
            && (int) $data['quantity'] === 0
        ) {
            throw ValidationException::withMessages([
                'quantity' => 'Stock In/Out quantity must be greater than zero.',
            ]);
        }

        DB::transaction(function () use (
            $business,
            $product,
            $data,
            $isVariant
        ) {
            // Lock the actual stock row to prevent simultaneous
            // requests from overwriting each other.
            if ($isVariant) {
                $stockItem = ProductVariant::query()
                    ->where('product_id', $product->id)
                    ->whereKey($data['variant_id'])
                    ->lockForUpdate()
                    ->firstOrFail();
            } else {
                $stockItem = Product::query()
                    ->where('business_id', $business->id)
                    ->whereKey($product->id)
                    ->lockForUpdate()
                    ->firstOrFail();
            }

            $before = (int) $stockItem->stock_quantity;
            $quantity = (int) $data['quantity'];

            $after = match ($data['movement_type']) {
                'stock_in' => $before + $quantity,
                'stock_out' => $before - $quantity,
                'adjustment' => $quantity,
            };

            if ($after < 0) {
                throw ValidationException::withMessages([
                    'quantity' => 'Insufficient stock. Available stock: '.$before,
                ]);
            }

            if ($after > 2147483647) {
                throw ValidationException::withMessages([
                    'quantity' => 'Stock quantity exceeds allowed limit.',
                ]);
            }

            if ($after === $before) {
                throw ValidationException::withMessages([
                    'quantity' => 'No stock change detected.',
                ]);
            }

            $stockItem->stock_quantity = $after;

            // Main product stock_status is maintained only
            // when main product quantity changes.
            if (! $isVariant) {
                $stockItem->stock_status = $after > 0
                    ? 'in_stock'
                    : 'out_of_stock';
            }

            $stockItem->save();

            ProductStockMovement::create([
                'business_id' => $business->id,
                'product_id' => $product->id,
                'product_variant_id' => $isVariant
                    ? $stockItem->id
                    : null,
                'user_id' => auth()->id(),
                'movement_type' => $data['movement_type'],
                'quantity_change' => $after - $before,
                'quantity_before' => $before,
                'quantity_after' => $after,
                'reason' => trim($data['reason']),
            ]);
        }, 3);

        return redirect()
            ->route(
                'businesses.products.inventory.index',
                [$business, $product]
            )
            ->with('success', 'Stock updated successfully.');
    }
}
