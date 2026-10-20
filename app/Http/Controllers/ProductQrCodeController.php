<?php

namespace App\Http\Controllers;

use App\Models\Business;
use App\Models\Product;
use App\Services\BusinessAccessService;
use App\Services\ProductQrCodeService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class ProductQrCodeController extends Controller
{
    public function __construct(
        private BusinessAccessService $access,
        private ProductQrCodeService $qrService
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

    public function show(Business $business, Product $product)
    {
        $this->authorizeAccess(
            $business,
            $product,
            'products.view'
        );

        return view('products.qr.show', [
            'business' => $business,
            'product' => $product,
            'publicUrl' => $this->qrService->publicUrl($product),
        ]);
    }

    public function generate(Business $business, Product $product)
    {
        $this->authorizeAccess(
            $business,
            $product,
            'products.update'
        );

        $this->qrService->generate($product);

        return redirect()
            ->route('businesses.products.qr.show', [
                $business,
                $product,
            ])
            ->with('success', 'QR Code generated successfully.');
    }

    public function download(Business $business, Product $product)
    {
        $this->authorizeAccess(
            $business,
            $product,
            'products.view'
        );

        abort_unless(
            $product->qr_code
            && Storage::disk('public')->exists($product->qr_code),
            404,
            'Generate QR Code first.'
        );

        return Storage::disk('public')->download(
            $product->qr_code,
            'product-'.$product->id.'-qr.png'
        );
    }

    public function print(Business $business, Product $product)
    {
        $this->authorizeAccess(
            $business,
            $product,
            'products.view'
        );

        abort_unless(
            $product->qr_code
            && Storage::disk('public')->exists($product->qr_code),
            404,
            'Generate QR Code first.'
        );

        return view('products.qr.print', compact(
            'business',
            'product'
        ));
    }

    public function bulkPrint(Request $request, Business $business)
    {
        abort_unless(
            auth()->check()
            && $this->access->can(
                auth()->user(),
                $business,
                'products.view'
            ),
            403
        );

        $data = $request->validate([
            'product_ids' => [
                'required',
                'array',
                'min:1',
                'max:50',
            ],
            'product_ids.*' => [
                'required',
                'integer',
                'distinct',
                Rule::exists('products', 'id')
                    ->where('business_id', $business->id)
                    ->whereNull('deleted_at'),
            ],
        ]);

        $products = $business->products()
            ->whereIn('id', $data['product_ids'])
            ->whereNotNull('qr_code')
            ->get()
            ->filter(function ($product) {
                return Storage::disk('public')
                    ->exists($product->qr_code);
            });

        if ($products->isEmpty()) {
            return back()->withErrors([
                'product_ids' => 'Selected products do not have saved QR codes.',
            ]);
        }

        return view('products.qr.bulk_print', compact(
            'business',
            'products'
        ));
    }
}
