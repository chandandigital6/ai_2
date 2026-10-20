<?php

namespace App\Http\Controllers;

use App\Models\Business;
use App\Models\Product;
use App\Models\ProductImage;
use App\Services\BusinessAccessService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class ProductImageController extends Controller
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

    private function checkImage(
        Product $product,
        ProductImage $image
    ): void {
        abort_unless(
            (int) $image->product_id === (int) $product->id,
            404
        );
    }

    public function index(Business $business, Product $product)
    {
        $this->authorizeAccess(
            $business,
            $product,
            'products.view'
        );

        $images = $product->images()
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        return view('products.gallery', compact(
            'business',
            'product',
            'images'
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
            'products.update'
        );

        $data = $request->validate([
            'images' => [
                'required',
                'array',
                'min:1',
                'max:10',
            ],
            'images.*' => [
                'required',
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:3072',
            ],
        ]);

        $uploadedPaths = [];

        try {
            DB::transaction(function () use (
                $request,
                $business,
                $product,
                &$uploadedPaths
            ) {
                $lastOrder = (int) $product->images()
                    ->max('sort_order');

                foreach ($request->file('images', []) as $file) {
                    $path = $file->store(
                        "businesses/{$business->id}/products/{$product->id}/gallery",
                        'public'
                    );

                    if (!$path) {
                        throw new \RuntimeException(
                            'Image upload failed.'
                        );
                    }

                    $uploadedPaths[] = $path;

                    $product->images()->create([
                        'image' => $path,
                        'image_type' => 'gallery',
                        'sort_order' => ++$lastOrder,
                        'alt_text' => $product->name,
                    ]);
                }
            });
        } catch (\Throwable $e) {
            Storage::disk('public')->delete($uploadedPaths);
            throw $e;
        }

        return redirect()
            ->route('businesses.products.gallery.index', [
                $business,
                $product
            ])
            ->with('success', 'Images uploaded successfully.');
    }

    public function update(
        Request $request,
        Business $business,
        Product $product,
        ProductImage $image
    ) {
        $this->authorizeAccess(
            $business,
            $product,
            'products.update'
        );

        $this->checkImage($product, $image);

        $data = $request->validate([
            'sort_order' => [
                'required',
                'integer',
                'min:0',
                'max:999999',
            ],
            'alt_text' => [
                'nullable',
                'string',
                'max:255',
            ],
        ]);

        $image->update($data);

        return back()->with(
            'success',
            'Image details updated.'
        );
    }

    public function featured(
        Business $business,
        Product $product,
        ProductImage $image
    ) {
        $this->authorizeAccess(
            $business,
            $product,
            'products.update'
        );

        $this->checkImage($product, $image);

        DB::transaction(function () use ($product, $image) {
            $product->images()
                ->where('id', '!=', $image->id)
                ->where('image_type', 'feature')
                ->update(['image_type' => 'gallery']);

            $image->update(['image_type' => 'feature']);

            // The product listing also uses featured_image.
            $product->update([
                'featured_image' => $image->image,
            ]);
        });

        return back()->with(
            'success',
            'Featured image updated.'
        );
    }

    public function destroy(
        Business $business,
        Product $product,
        ProductImage $image
    ) {
        $this->authorizeAccess(
            $business,
            $product,
            'products.update'
        );

        $this->checkImage($product, $image);

        $path = $image->image;

        $isFeatured = $product->featured_image === $path;

        DB::transaction(function () use (
            $product,
            $image,
            $isFeatured
        ) {
            $image->delete();

            if ($isFeatured) {
                $replacement = $product->images()
                    ->orderBy('sort_order')
                    ->orderBy('id')
                    ->first();

                $product->update([
                    'featured_image' => $replacement?->image,
                ]);

                if ($replacement) {
                    $product->images()
                        ->where('id', '!=', $replacement->id)
                        ->where('image_type', 'feature')
                        ->update(['image_type' => 'gallery']);

                    $replacement->update([
                        'image_type' => 'feature',
                    ]);
                }
            }
        });

        // Do not remove a file still referenced elsewhere.
        $stillUsed = ProductImage::where('image', $path)->exists()
            || Product::where('featured_image', $path)->exists();

        if (!$stillUsed && $path) {
            Storage::disk('public')->delete($path);
        }

        return back()->with(
            'success',
            'Image removed successfully.'
        );
    }
}
