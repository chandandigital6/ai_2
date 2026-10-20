<?php

namespace App\Http\Controllers;

use App\Models\Business;
use App\Models\Category;
use App\Models\City;
use App\Models\Product;
use App\Models\Service;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class PublicGlobalSearchController extends Controller
{
    private function approvedBusinesses()
    {
        return Business::query()->where('status', 'active')->where('verification_status', 'approved');
    }

    private function approvedItems($model)
    {
        return $model::query()->where('status', 'active')
            ->whereHas('business', fn($q) => $q->where('status', 'active')->where('verification_status', 'approved'));
    }

    public function index(Request $request)
    {
        $data = $request->validate([
            'q' => ['nullable', 'string', 'max:120'],
            'type' => ['nullable', Rule::in(['businesses', 'products', 'services'])],
            'category_id' => ['nullable', 'integer', 'min:1'],
            'city_id' => ['nullable', 'integer', 'min:1'],
            'min_price' => ['nullable', 'numeric', 'min:0', 'max:999999999999'],
            'max_price' => ['nullable', 'numeric', 'min:0', 'max:999999999999', 'gte:min_price'],
        ]);

        $q = trim($data['q'] ?? '');
        $type = $data['type'] ?? 'businesses';
        $categoryId = $data['category_id'] ?? null;
        $cityId = $data['city_id'] ?? null;
        $min = $data['min_price'] ?? null;
        $max = $data['max_price'] ?? null;

        $categoryType = ['businesses' => 'business', 'products' => 'product', 'services' => 'service'][$type];
        $categories = Category::query()->where('category_type', $categoryType)
            ->where('is_active', true)->orderBy('name')->get(['id', 'name']);
        $cities = City::query()->whereIn('id', $this->approvedBusinesses()->whereNotNull('city_id')->select('city_id'))
            ->orderBy('name')->get(['id', 'name']);

        $businesses = $this->approvedBusinesses();
        $products = $this->approvedItems(Product::class);
        $services = $this->approvedItems(Service::class);

        if ($q !== '') {
            $like = '%' . addcslashes($q, '%_\\') . '%';
            $businesses->where(fn($x) => $x->where('name', 'like', $like)->orWhere('description', 'like', $like)->orWhere('address', 'like', $like));
            foreach ([$products, $services] as $itemQuery) {
                $itemQuery->where(fn($x) => $x->where('name', 'like', $like)->orWhere('description', 'like', $like)->orWhereHas('business', fn($b) => $b->where('name', 'like', $like)));
            }
        }

        if ($cityId) {
            $businesses->where('city_id', $cityId);
            $products->whereHas('business', fn($b) => $b->where('city_id', $cityId));
            $services->whereHas('business', fn($b) => $b->where('city_id', $cityId));
        }

        if ($categoryId) {
            if ($type === 'businesses') $businesses->whereHas('category', fn($c) => $c->whereKey($categoryId)->where('category_type', 'business')->where('is_active', true));
            if ($type === 'products') $products->whereHas('category', fn($c) => $c->whereKey($categoryId)->where('category_type', 'product')->where('is_active', true));
            if ($type === 'services') $services->whereHas('category', fn($c) => $c->whereKey($categoryId)->where('category_type', 'service')->where('is_active', true));
        }

        if ($min !== null || $max !== null) {
            foreach ([$products, $services] as $query) {
                $price = 'COALESCE(offer_price, price)';
                if ($min !== null) $query->whereRaw("{$price} >= ?", [$min]);
                if ($max !== null) $query->whereRaw("{$price} <= ?", [$max]);
            }
        }

        $results = match ($type) {
            'products' => $products->with(['business:id,name,slug', 'category:id,name'])->latest('id')->paginate(12),
            'services' => $services->with(['business:id,name,slug', 'category:id,name'])->latest('id')->paginate(12),
            default => $businesses->with(['category:id,name', 'city:id,name'])->latest('id')->paginate(12),
        };

        return view('public.search.index', compact('results', 'type', 'q', 'categories', 'cities', 'categoryId', 'cityId', 'min', 'max'));
    }

    // Same-origin web endpoint used by the search input; no REST API is introduced.
    public function suggest(Request $request)
    {
        $data = $request->validate(['q' => ['required', 'string', 'min:2', 'max:80']]);
        $q = trim($data['q']);
        if (mb_strlen($q) < 2) return response()->json(['items' => []]);
        $like = '%' . addcslashes($q, '%_\\') . '%';

        $businesses = $this->approvedBusinesses()->where('name', 'like', $like)
            ->orderBy('name')->limit(4)->get(['name', 'slug']);
        $products = $this->approvedItems(Product::class)->where('name', 'like', $like)
            ->with('business:id,slug')->orderBy('name')->limit(4)->get(['id', 'name', 'slug', 'business_id']);
        $services = $this->approvedItems(Service::class)->where('name', 'like', $like)
            ->with('business:id,slug')->orderBy('name')->limit(4)->get(['id', 'name', 'slug', 'business_id']);

        $items = [];
        foreach ($businesses as $b) $items[] = ['label' => $b->name, 'type' => 'Business', 'url' => route('public.business.home', $b->slug)];
        foreach ($products as $p) $items[] = ['label' => $p->name, 'type' => 'Product', 'url' => route('public.business.product', [$p->business->slug, $p->slug])];
        foreach ($services as $s) $items[] = ['label' => $s->name, 'type' => 'Service', 'url' => route('public.business.service', [$s->business->slug, $s->slug])];

        return response()->json(['items' => $items]);
    }
}
