<?php

namespace App\Http\Controllers;

use App\Models\Business;
use App\Models\Category;
use App\Models\City;
use App\Models\State;
use App\Services\BusinessAccessService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class GrowyaNearbyController extends Controller
{
    public function index(Request $request)
    {
        $f = $request->validate([
            'q' => ['nullable', 'string', 'max:120'],
            'category' => ['nullable', 'string', 'max:160'],
            'state_id' => ['nullable', 'integer', Rule::exists('states', 'id')->where('is_active', 1)],
            'city_id' => ['nullable', 'integer', Rule::exists('cities', 'id')->where('is_active', 1)],
            'lat' => ['nullable', 'required_with:lng', 'numeric', 'between:-90,90'],
            'lng' => ['nullable', 'required_with:lat', 'numeric', 'between:-180,180'],
            'radius' => ['nullable', Rule::in(['5', '10', '25', '50'])],
            'sort' => ['nullable', Rule::in(['nearest', 'newest', 'popular'])],
        ]);

        if (!empty($f['city_id']) && !empty($f['state_id'])) {
            $valid = City::query()->whereKey($f['city_id'])->where('state_id', $f['state_id'])->exists();
            if (!$valid) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'city_id' => 'Choose a city belonging to the selected state.',
                ]);
            }
        }

        $query = Business::query()
            ->where('status', 'active')
            ->where('verification_status', 'approved')
            ->with(['category:id,name,slug', 'city:id,name', 'state:id,name']);

        if (!empty($f['q'])) {
            $term = trim($f['q']);
            $query->where(function ($q) use ($term) {
                $q->where('name', 'like', '%' . $term . '%')
                    ->orWhere('address', 'like', '%' . $term . '%')
                    ->orWhereHas('category', fn($c) => $c->where('name', 'like', '%' . $term . '%'));
            });
        }
        if (!empty($f['category'])) {
            $query->whereHas('category', fn($q) => $q->where('slug', $f['category'])
                ->where('category_type', 'business')->where('is_active', true));
        }
        if (!empty($f['state_id'])) $query->where('state_id', (int) $f['state_id']);
        if (!empty($f['city_id'])) $query->where('city_id', (int) $f['city_id']);

        $geo = isset($f['lat'], $f['lng']);
        if ($geo) {
            // Great-circle distance in kilometres; clamp the acos input to [-1,1] for rounding safety.
            $lat = (float) $f['lat'];
            $lng = (float) $f['lng'];
            $radius = (int) ($f['radius'] ?? 25);
            $distanceSql = '(6371 * ACOS(LEAST(1, GREATEST(-1, '
                . 'COS(RADIANS(?)) * COS(RADIANS(latitude)) '
                . '* COS(RADIANS(longitude) - RADIANS(?)) '
                . '+ SIN(RADIANS(?)) * SIN(RADIANS(latitude)) ))))';
            $query->whereNotNull('latitude')->whereNotNull('longitude')
                ->whereBetween('latitude', [-90, 90])
                ->whereBetween('longitude', [-180, 180])
                ->whereRaw($distanceSql . ' <= ?', [$lat, $lng, $lat, $radius]);
            $query->select('businesses.*')->selectRaw($distanceSql . ' AS distance_km', [$lat, $lng, $lat]);
            if (($f['sort'] ?? 'nearest') === 'popular') {
                $query->orderByDesc('views_count');
            } elseif (($f['sort'] ?? 'nearest') === 'newest') {
                $query->latest('id');
            } else {
                $query->orderBy('distance_km');
            }
            $query->orderBy('id');
        } else {
            if (($f['sort'] ?? 'newest') === 'popular') $query->orderByDesc('views_count');
            else $query->latest('id');
        }

        $businesses = $query->paginate(12)->withQueryString();
        $categories = Category::query()->where('category_type', 'business')
            ->where('is_active', true)->orderBy('name')->get(['id', 'name', 'slug']);
        $states = State::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']);
        $cities = City::query()->where('is_active', true)
            ->when(!empty($f['state_id']), fn($q) => $q->where('state_id', $f['state_id']))
            ->orderBy('name')->get(['id', 'name', 'state_id']);
        return view('growya-location.nearby', compact('businesses', 'categories', 'states', 'cities', 'geo'));
    }

    public function edit(Request $request, Business $business)
    {
        $this->authorizeLocation($request, $business);
        return view('growya-location.edit', compact('business'));
    }

    public function update(Request $request, Business $business)
    {
        $this->authorizeLocation($request, $business);
        $data = $request->validate([
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
        ]);
        $business->update($data);
        return back()->with('success', 'Business map location updated.');
    }

    private function authorizeLocation(Request $request, Business $business): void
    {
        $user = $request->user();
        abort_unless($user && (
            $user->hasRole('super_admin', 'web') ||
            ((int) $business->owner_id === (int) $user->id &&
                $business->status === 'active' &&
                app(BusinessAccessService::class)->can($user, $business, 'business.update'))
        ), 403);
    }
}
