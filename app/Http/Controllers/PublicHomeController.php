<?php

namespace App\Http\Controllers;

use App\Models\Business;
use App\Models\Category;
use App\Models\Product;
use App\Models\Service;
use App\Services\GrowyaFeaturedEligibilityService;
use App\Services\GrowyaPaidPromotionService;
use Illuminate\View\View;

class PublicHomeController extends Controller
{
    public function index(): View
    {
        $visibleBusinessIds = Business::query()
            ->where('status', 'active')
            ->where('verification_status', 'approved')
            ->select('id');

        $categories = Category::query()
            ->where('category_type', 'business')
            ->where('is_active', true)
            ->whereHas('businesses', fn ($q) => $q
                ->where('status', 'active')
                ->where('verification_status', 'approved'))
            ->orderBy('sort_order')
            ->orderBy('name')
            ->limit(12)
            ->get(['id', 'name', 'slug', 'icon', 'image']);

        $paidService = app(GrowyaPaidPromotionService::class);
        $eligibleIds = app(GrowyaFeaturedEligibilityService::class)->eligibleIds();
        $featuredBusinesses = Business::query()
            ->where('status', 'active')
            ->where('verification_status', 'approved')
            ->with(['category:id,name', 'city:id,name'])
            ->sponsoredFirst()
            ->when($eligibleIds, fn ($q) => $q->orderByRaw('CASE WHEN businesses.id IN ('.implode(',', array_map('intval', $eligibleIds)).') THEN 0 ELSE 1 END'))
            ->orderByDesc('views_count')
            ->orderByDesc('id')
            ->limit(6)
            ->get();

        $paidProductIds = $paidService->productIds();
        $featuredProducts = Product::query()
            ->whereIn('business_id', $visibleBusinessIds)
            ->where('status', 'active')
            ->where(function ($q) use ($paidProductIds) {
                $q->where('is_featured', true)->orWhereIn('id', $paidProductIds);
            })
            ->with('business:id,name,slug')
            ->sponsoredFirst()
            ->orderByDesc('id')
            ->limit(8)
            ->get();

        $paidPromotions = $paidService->forBusinesses($featuredBusinesses->pluck('id'));
        $paidProductPromotions = $paidService->forProducts($featuredProducts->pluck('id'));
        $paidService->countImpressions($paidPromotions->keys()->all(), $paidProductPromotions->keys()->all());
        $popularServices = Service::query()
            ->whereIn('business_id', $visibleBusinessIds)
            ->where('status', 'active')
            ->with('business:id,name,slug')
            ->orderByDesc('views_count')
            ->orderByDesc('id')
            ->limit(6)
            ->get();

        $stats = [
            'businesses' => Business::query()
                ->where('status', 'active')
                ->where('verification_status', 'approved')->count(),
            'products' => Product::query()
                ->where('status', 'active')
                ->whereIn('business_id', $visibleBusinessIds)->count(),
            'services' => Service::query()
                ->where('status', 'active')
                ->whereIn('business_id', $visibleBusinessIds)->count(),
        ];

        return view('public.home.index', compact(
            'categories', 'featuredBusinesses', 'featuredProducts', 'popularServices', 'stats', 'paidPromotions', 'paidProductPromotions'
        ));
    }
}
