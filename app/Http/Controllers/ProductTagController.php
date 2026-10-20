<?php

namespace App\Http\Controllers;

use App\Models\Business;
use App\Models\Product;
use App\Models\Tag;
use App\Services\BusinessAccessService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class ProductTagController extends Controller
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

    public function index(Business $business, Product $product)
    {
        $this->authorizeAccess(
            $business,
            $product,
            'products.view'
        );

        $tags = Tag::query()
            ->orderBy('name')
            ->get();

        $selectedTags = $product->tags()
            ->pluck('tags.id')
            ->map(fn ($id) => (int) $id)
            ->all();

        return view('products.tags', compact(
            'business',
            'product',
            'tags',
            'selectedTags'
        ));
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
            'tags' => ['sometimes', 'array'],
            'tags.*' => [
                'required',
                'integer',
                'distinct',
                Rule::exists('tags', 'id'),
            ],
        ]);

        DB::transaction(function () use ($product, $data) {
            $product->tags()->sync($data['tags'] ?? []);
        });

        return redirect()
            ->route('businesses.products.tags.index', [
                $business,
                $product,
            ])
            ->with(
                'success',
                'Product tags updated successfully.'
            );
    }
}
