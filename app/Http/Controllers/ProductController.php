<?php

namespace App\Http\Controllers;

use App\Models\Business;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductImage;
use App\Services\BusinessAccessService;
use App\Services\GrowyaPlanLimitService;
use App\Services\ProductQrCodeService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ProductController extends Controller
{
    public function __construct(
        private BusinessAccessService $access
    ) {}

    private function authorizeBusiness(
        Business $business,
        string $permission
    ): void {
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

    private function categories(Business $business)
    {
        return Category::query()->where(fn ($q) => $q->whereNull('business_id')->orWhere('business_id', $business->id))
            ->where('category_type', 'product')
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'parent_id']);
    }

    public function index(Request $request, Business $business)
    {
        $this->authorizeBusiness($business, 'products.view');

        $query = $business->products()
            ->with('category:id,name');

        if ($request->filled('search')) {
            $search = trim((string) $request->search);

            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('sku', 'like', "%{$search}%");
            });
        }

        if ($request->filled('status')) {
            $status = $request->input('status');

            if (in_array($status, ['active', 'inactive'], true)) {
                $query->where('status', $status);
            }
        }

        if ($request->input('product_type') === 'featured') {
            $query->where('is_featured', true);
        }

        if ($request->input('product_type') === 'trending') {
            $query->where('is_trending', true);
        }

        $products = $query
            ->latest('id')
            ->paginate(15)
            ->withQueryString();

        return view('products.index', compact(
            'business',
            'products'
        ));
    }

    public function create(Business $business)
    {
        $this->authorizeBusiness($business, 'products.create');

        return view('products.form', [
            'business' => $business,
            'product' => new Product,
            'categories' => $this->categories($business),
        ]);
    }

    public function store(Request $request, Business $business)
    {
        $this->authorizeBusiness($business, 'products.create');

        $data = $this->validated($request, $business);

        $image = $request->file('featured_image');

        unset($data['featured_image']);

        $imagePath = null;

        try {
            if ($image) {
                $imagePath = $image->store(
                    "businesses/{$business->id}/products",
                    'public'
                );

                if (! $imagePath) {
                    throw new \RuntimeException('Image upload failed.');
                }

                $data['featured_image'] = $imagePath;
            }

            $product = DB::transaction(function () use (
                $business,
                $data
            ) {
                Business::whereKey($business->id)->lockForUpdate()->firstOrFail();
                app(GrowyaPlanLimitService::class)->assertCanAdd($business, 'max_products');
                $data['slug'] = $this->generateSlug(
                    $business,
                    $data['name']
                );

                return $business->products()->create($data);
            });

            try {
                app(ProductQrCodeService::class)->generate($product);
            } catch (\Throwable $qrError) {
                Log::warning('Product saved; QR can be regenerated.', ['product_id' => $product->id, 'error' => $qrError->getMessage()]);
            }

        } catch (\Throwable $e) {
            if ($imagePath) {
                Storage::disk('public')->delete($imagePath);
            }

            throw $e;
        }

        return redirect()
            ->route('businesses.products.index', $business)
            ->with('success', 'Product created successfully.');
    }

    public function show(Business $business, Product $product)
    {
        $this->authorizeBusiness($business, 'products.view');
        $this->checkProduct($business, $product);

        $product->load([
            'business:id,name',
            'category:id,name',
            'images',
            'variants',
            'tags',
            'attributeValues.attribute',
        ]);

        return view('products.show', compact(
            'business',
            'product'
        ));
    }

    public function edit(Business $business, Product $product)
    {
        $this->authorizeBusiness($business, 'products.update');
        $this->checkProduct($business, $product);

        return view('products.form', [
            'business' => $business,
            'product' => $product,
            'categories' => $this->categories($business),
        ]);
    }

    public function update(
        Request $request,
        Business $business,
        Product $product
    ) {
        $this->authorizeBusiness($business, 'products.update');
        $this->checkProduct($business, $product);

        $data = $this->validated(
            $request,
            $business,
            $product
        );

        $image = $request->file('featured_image');

        // Stock edit se change nahi hota (variants se manage hota hai).
        unset(
            $data['featured_image'],
            $data['stock_status'],
            $data['stock_quantity']
        );

        if ($product->name !== $data['name']) {
            $data['slug'] = $this->generateSlug(
                $business,
                $data['name'],
                $product->id
            );
        }

        $oldImage = $product->featured_image;
        $newImage = null;

        try {
            if ($image) {
                $newImage = $image->store(
                    "businesses/{$business->id}/products",
                    'public'
                );

                if (! $newImage) {
                    throw new \RuntimeException('Image upload failed.');
                }

                $data['featured_image'] = $newImage;
            }

            $product->update($data);
        } catch (\Throwable $e) {
            if ($newImage) {
                Storage::disk('public')->delete($newImage);
            }

            throw $e;
        }

        if ($newImage && $oldImage) {
            $usedInGallery = ProductImage::query()
                ->where('image', $oldImage)
                ->exists();

            if (! $usedInGallery) {
                Storage::disk('public')->delete($oldImage);
            }
        }

        return redirect()
            ->route('businesses.products.index', $business)
            ->with('success', 'Product updated successfully.');
    }

    public function destroy(Business $business, Product $product)
    {
        $this->authorizeBusiness($business, 'products.delete');
        $this->checkProduct($business, $product);

        // Retain images for possible product recovery.
        $product->delete();

        return redirect()
            ->route('businesses.products.index', $business)
            ->with('success', 'Product moved to trash.');
    }

    private function checkProduct(
        Business $business,
        Product $product
    ): void {
        abort_unless(
            (int) $product->business_id === (int) $business->id,
            404
        );
    }

    private function validated(
        Request $request,
        Business $business,
        ?Product $product = null
    ): array {
        $isCreate = $product === null;

        // Stock rules sirf create pe.
        $stockRules = $isCreate
            ? [
                'stock_status' => [
                    'required',
                    Rule::in(['in_stock', 'out_of_stock']),
                ],
                'stock_quantity' => [
                    'required',
                    'integer',
                    'min:0',
                ],
            ]
            : [];

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],

            'category_id' => [
                'nullable',
                Rule::exists('categories', 'id')->where(fn ($q) => $q->whereNull('business_id')->orWhere('business_id', $business->id))
                    ->where('category_type', 'product')
                    ->where('is_active', true),
            ],

            'sku' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('products', 'sku')
                    ->where('business_id', $business->id)
                    ->withoutTrashed()
                    ->ignore($product?->id),
            ],

            'description' => ['nullable', 'string', 'max:20000'],

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

            ...$stockRules,

            'featured_image' => [
                'nullable',
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:3072',
            ],

            'video' => ['nullable', 'url:http,https', 'max:255'],

            'status' => [
                'required',
                Rule::in(['active', 'inactive']),
            ],

            'is_featured' => ['required', 'boolean'],
            'is_trending' => ['required', 'boolean'],

            'meta_title' => ['nullable', 'string', 'max:255'],
            'meta_description' => ['nullable', 'string', 'max:1000'],
            'meta_keywords' => ['nullable', 'string', 'max:1000'],

            'specifications' => ['nullable', 'array', 'max:30'],
            'specifications.*.name' => ['nullable', 'string', 'max:255'],
            'specifications.*.value' => ['nullable', 'string', 'max:1000'],
        ]);

        // Stock consistency check: sirf create pe.
        if ($isCreate) {
            if (
                $data['stock_status'] === 'out_of_stock'
                && (int) $data['stock_quantity'] > 0
            ) {
                throw ValidationException::withMessages([
                    'stock_quantity' => 'Out of stock products must have zero quantity.',
                ]);
            }

            if (
                $data['stock_status'] === 'in_stock'
                && (int) $data['stock_quantity'] === 0
            ) {
                throw ValidationException::withMessages([
                    'stock_quantity' => 'In-stock products must have quantity greater than zero.',
                ]);
            }
        }

        // Convert checkboxes to booleans.
        $data['is_featured'] = (bool) $data['is_featured'];
        $data['is_trending'] = (bool) $data['is_trending'];

        // Normalize SEO input.
        foreach (['meta_title', 'meta_description', 'meta_keywords'] as $field) {
            $data[$field] = isset($data[$field])
                ? trim($data[$field])
                : null;

            if ($data[$field] === '') {
                $data[$field] = null;
            }
        }

        // Clean specifications.
        $cleanSpecifications = [];

        foreach ($data['specifications'] ?? [] as $specification) {
            $name = trim((string) ($specification['name'] ?? ''));
            $value = trim((string) ($specification['value'] ?? ''));

            if ($name === '' && $value === '') {
                continue;
            }

            if ($name === '' || $value === '') {
                throw ValidationException::withMessages([
                    'specifications' => 'Every specification must have both name and value.',
                ]);
            }

            $cleanSpecifications[] = [
                'name' => $name,
                'value' => $value,
            ];
        }

        $data['specifications'] = $cleanSpecifications;

        return $data;
    }

    private function generateSlug(
        Business $business,
        string $name,
        ?int $exceptId = null
    ): string {
        $base = Str::slug($name) ?: 'product';
        $slug = $base;
        $number = 2;

        while (
            Product::withTrashed()
                ->where('business_id', $business->id)
                ->where('slug', $slug)
                ->when(
                    $exceptId,
                    fn ($q) => $q->where('id', '!=', $exceptId)
                )
                ->exists()
        ) {
            $slug = $base.'-'.$number++;
        }

        return $slug;
    }
}
