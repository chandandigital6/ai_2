<?php

namespace App\Http\Controllers;

use App\Support\PublicOfferDisplay;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PublicOffersExploreController extends Controller
{
    public function index(Request $request)
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:120'],
            'type' => ['nullable', Rule::in(['percentage', 'fixed', 'announcement'])],
        ]);

        $query = PublicOfferDisplay::visible()->with('business:id,name,slug');

        if (filled($filters['q'] ?? null)) {
            $search = trim($filters['q']);
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhereHas('business', fn ($b) => $b->where('name', 'like', "%{$search}%"));
            });
        }

        if (filled($filters['type'] ?? null)) {
            $query->where('offer_type', $filters['type']);
        }

        $offers = $query->latest('id')->paginate(12)->withQueryString();
        return view('public.offers.index', compact('offers'));
    }
}
