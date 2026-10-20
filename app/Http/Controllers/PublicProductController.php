<?php

namespace App\Http\Controllers;

use App\Models\Business;
use App\Models\Product;

class PublicProductController extends Controller
{
    public function show(Business $business, Product $product)
    {
        abort_unless(
            (int) $product->business_id === (int) $business->id,
            404
        );

        // Public visitors can see active businesses/products only.
        abort_unless(
            $business->status === 'active'
            && $product->status === 'active',
            404
        );

        $product->load([
            'category:id,name',
            'images',
            'variants',
        ]);

        return view('public.products.show', compact(
            'business',
            'product'
        ));
    }
}
