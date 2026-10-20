<?php

namespace App\Http\Controllers;

use App\Models\Business;
use App\Models\Category;
use App\Models\City;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PublicMarketplaceController extends Controller
{
    public function index(Request $request)
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:120'],
            'category' => ['nullable', 'string', 'max:160'],
            'city' => ['nullable', 'string', 'max:120'],
            'sort' => ['nullable', Rule::in(['newest', 'popular', 'name'])],
        ]);

        $query = Business::query()
            ->where('status', 'active')
            ->where('verification_status', 'approved')
            ->with(['category:id,name,slug', 'city:id,name', 'state:id,name']);

        if (!empty($filters['q'])) {
            $term = trim($filters['q']);
            $query->where(function ($sub) use ($term) {
                $sub->where('name', 'like', "%{$term}%")
                    ->orWhere('description', 'like', "%{$term}%")
                    ->orWhere('address', 'like', "%{$term}%")
                    ->orWhereHas('category', function ($cat) use ($term) {
                        $cat->where('name', 'like', "%{$term}%");
                    });
            });
        }

        if (!empty($filters['category'])) {
            $query->whereHas('category', function ($cat) use ($filters) {
                $cat->where('slug', $filters['category'])
                    ->where('category_type', 'business')
                    ->where('is_active', true);
            });
        }

        if (!empty($filters['city'])) {
            $cityName = trim($filters['city']);
            $query->whereHas('city', function ($city) use ($cityName) {
                $city->where('name', 'like', "%{$cityName}%");
            });
        }

        // Paid promoted businesses rank first; subscription featured businesses remain next.
        $paidService = app(\App\Services\GrowyaPaidPromotionService::class);
        $paidIds = $paidService->businessIds();
        if ($paidIds && ($filters['sort'] ?? 'newest') !== 'name') {
            $query->orderByRaw('CASE WHEN businesses.id IN ('.implode(',', array_map('intval', $paidIds)).') THEN 0 ELSE 1 END');
        }
        // Featured boost is based on active subscription/override, not a static flag.
        // Keep explicit name sorting alphabetical.
        if (($filters['sort'] ?? 'newest') !== 'name') {
            $featuredIds = app(\App\Services\GrowyaFeaturedEligibilityService::class)->eligibleIds();
            if ($featuredIds) {
                $query->orderByRaw('CASE WHEN businesses.id IN ('.implode(',',array_map('intval',$featuredIds)).') THEN 0 ELSE 1 END');
            }
        }
        switch ($filters['sort'] ?? 'newest') {
            case 'popular':
                $query->orderByDesc('views_count')->orderByDesc('id');
                break;
            case 'name':
                $query->orderBy('name')->orderByDesc('id');
                break;
            default:
                $query->latest('id');
        }

        $businesses = $query->paginate(12)->withQueryString();
        $paidPromotions = \App\Models\GrowyaPaidPromotion::query()->live()
            ->whereHas('package', fn($q)=>$q->where('target_type','business'))
            ->whereIn('business_id',$businesses->pluck('id'))
            ->orderByDesc('id')->get()->unique('business_id')->keyBy('business_id');
        $paidService->countImpressions($paidPromotions->keys()->all());
        $categories = Category::query()
            ->where('category_type', 'business')
            ->where('is_active', true)
            ->whereHas('businesses', function ($businesses) {
                $businesses->where('status', 'active')
                    ->where('verification_status', 'approved');
            })
            ->orderBy('name')
            ->get(['id', 'name', 'slug']);

        return view('public.marketplace.index', compact('businesses', 'categories'));
    }
}
