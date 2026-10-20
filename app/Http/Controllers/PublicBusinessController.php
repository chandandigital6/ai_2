<?php

namespace App\Http\Controllers;

use App\Models\Business;
use App\Models\Product;
use App\Models\Service;
use App\Models\BusinessGallery;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Carbon;

class PublicBusinessController extends Controller
{
    private function business(string $slug): Business
    {
        return Business::query()
            ->where('slug', $slug)
            ->where('status', 'active')
            ->where('verification_status', 'approved')
            ->firstOrFail();
    }

    private function productsQuery(Business $business)
    {
        $query = Product::query()
            ->where('business_id', $business->id);

        if (Schema::hasColumn('products', 'status')) {
            $query->where('status', 'active');
        } elseif (Schema::hasColumn('products', 'is_active')) {
            $query->where('is_active', true);
        } else {
            // Fail closed if no public visibility field exists.
            $query->whereRaw('1 = 0');
        }

        return $query;
    }

    private function servicesQuery(Business $business)
    {
        return Service::query()
            ->where('business_id', $business->id)
            ->where('status', 'active');
    }

    private function galleryQuery(Business $business)
    {
        return BusinessGallery::query()
            ->where('business_id', $business->id)
            ->where('is_active', true);
    }

    public function home(string $slug)
    {
        $business = $this->business($slug);
        $business->load(['category:id,name', 'city:id,name', 'state:id,name', 'hours', 'socialLinks']);

        $products = $this->productsQuery($business)
            ->with('category:id,name')
            ->latest('id')
            ->limit(8)
            ->get();

        $services = $this->servicesQuery($business)
            ->latest('id')
            ->limit(6)
            ->get();

        $gallery = $this->galleryQuery($business)
            ->orderBy('sort_order')
            ->limit(6)
            ->get();

        // Day mapping is 0=Sunday through 6=Saturday, from existing migration.
        $now = Carbon::now(config('app.timezone'));
        $todayHour = $business->hours->firstWhere('day_of_week', $now->dayOfWeek);
        $isOpen = null; // Unknown if hours not configured.
        if ($todayHour) {
            $isOpen = !$todayHour->is_closed
                && $todayHour->open_time
                && $todayHour->close_time
                && $now->format('H:i:s') >= $todayHour->open_time
                && $now->format('H:i:s') < $todayHour->close_time;
        }

        return view('public.businesses.home', compact(
            'business', 'products', 'services', 'gallery',
            'todayHour', 'isOpen'
        ));
    }

    public function products(string $slug)
    {
        $business = $this->business($slug);

        $products = $this->productsQuery($business)
            ->latest('id')
            ->paginate(12);

        return view('public.businesses.products', compact(
            'business',
            'products'
        ));
    }

    // public function product(string $slug, string $productSlug)
    // {
    //     $business = $this->business($slug);

    //     $product = $this->productsQuery($business)
    //         ->where('slug', $productSlug)
    //         ->firstOrFail();

    //     return view('public.businesses.product', compact(
    //         'business',
    //         'product'
    //     ));
    // }


     public function product(string $slug, string $productSlug)
    {
        $business = $this->business($slug);

        $product = $this->productsQuery($business)
            ->where('slug', $productSlug)
            ->with([
                'category:id,name',
                'images' => fn ($q) => $q->orderBy('sort_order')->orderBy('id'),
                'variants' => fn ($q) => $q->where('status', 'active')
                    ->with('attributeValues.attribute')
                    ->orderBy('id'),
                'attributeValues.attribute',
            ])
            ->firstOrFail();

        return view('public.businesses.product', compact('business', 'product'));
    }

    public function services(string $slug)
    {
        $business = $this->business($slug);

        $services = $this->servicesQuery($business)
            ->latest('id')
            ->paginate(12);

        return view('public.businesses.services', compact(
            'business',
            'services'
        ));
    }

    public function service(string $slug, string $serviceSlug)
    {
        $business = $this->business($slug);

        $service = $this->servicesQuery($business)
            ->where('slug', $serviceSlug)
            ->with('images')
            ->firstOrFail();

        return view('public.businesses.service', compact(
            'business',
            'service'
        ));
    }

    public function gallery(string $slug)
    {
        $business = $this->business($slug);

        $gallery = $this->galleryQuery($business)
            ->orderBy('sort_order')
            ->orderByDesc('id')
            ->paginate(18);

        return view('public.businesses.gallery', compact(
            'business',
            'gallery'
        ));
    }

    public function contact(string $slug)
    {
        $business = $this->business($slug);

        return view('public.businesses.contact', compact(
            'business'
        ));
    }
}
