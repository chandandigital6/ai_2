<?php

namespace App\Http\Controllers;

use App\Models\Business;
use App\Models\Category;
use App\Models\Product;
use App\Models\Service;
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

        $paidService=app(\App\Services\GrowyaPaidPromotionService::class);
        $paidIds=$paidService->businessIds();
        $eligibleIds = app(\App\Services\GrowyaFeaturedEligibilityService::class)->eligibleIds();
        $featuredBusinesses = Business::query()
            ->where('status', 'active')
            ->where('verification_status', 'approved')
            ->with(['category:id,name', 'city:id,name'])
            ->when($paidIds, fn($q) => $q->orderByRaw('CASE WHEN businesses.id IN ('.implode(',',array_map('intval',$paidIds)).') THEN 0 ELSE 1 END'))
            ->when($eligibleIds, fn($q) => $q->orderByRaw('CASE WHEN businesses.id IN ('.implode(',',array_map('intval',$eligibleIds)).') THEN 0 ELSE 1 END'))
            ->orderByDesc('views_count')
            ->orderByDesc('id')
            ->limit(6)
            ->get();

        $paidProductIds=$paidService->productIds();
        $featuredProducts = Product::query()
            ->whereIn('business_id', $visibleBusinessIds)
            ->where('status', 'active')
            ->where(function($q)use($paidProductIds){$q->where('is_featured',true)->orWhereIn('id',$paidProductIds);})
            ->with('business:id,name,slug')
            ->when($paidProductIds,fn($q)=>$q->orderByRaw('CASE WHEN products.id IN ('.implode(',',array_map('intval',$paidProductIds)).') THEN 0 ELSE 1 END'))
            ->orderByDesc('id')
            ->limit(8)
            ->get();

        $paidPromotions = \App\Models\GrowyaPaidPromotion::query()->live()
            ->whereHas('package',fn($q)=>$q->where('target_type','business'))
            ->whereIn('business_id',$featuredBusinesses->pluck('id'))
            ->latest('id')->get()->unique('business_id')->keyBy('business_id');
        $paidProductPromotions = \App\Models\GrowyaPaidPromotion::query()->live()
            ->whereHas('package',fn($q)=>$q->where('target_type','product'))
            ->whereIn('product_id',$featuredProducts->pluck('id'))
            ->latest('id')->get()->unique('product_id')->keyBy('product_id');
        $paidService->countImpressions($paidPromotions->keys()->all(),$paidProductPromotions->keys()->all());
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
            'categories', 'featuredBusinesses', 'featuredProducts', 'popularServices', 'stats', 'paidPromotions','paidProductPromotions'
        ));
    }
}
