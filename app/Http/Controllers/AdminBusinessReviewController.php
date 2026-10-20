<?php

namespace App\Http\Controllers;

use App\Models\Review;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AdminBusinessReviewController extends Controller
{
    private function authorizeAdmin(): void
    {
        abort_unless(auth()->check() && auth()->user()->hasRole('super_admin', 'web'), 403);
    }

    public function index(Request $request)
    {
        $this->authorizeAdmin();

        $data = $request->validate([
            'status' => ['nullable', Rule::in(['pending', 'approved', 'rejected', 'all'])],
        ]);

        $status = $data['status'] ?? 'pending';

        // Ek hi query me sab status ke counts.
        $raw = Review::query()
            ->selectRaw('status, COUNT(*) as c')
            ->groupBy('status')
            ->pluck('c', 'status');

        $counts = [
            'pending' => (int) ($raw['pending'] ?? 0),
            'approved' => (int) ($raw['approved'] ?? 0),
            'rejected' => (int) ($raw['rejected'] ?? 0),
        ];
        $counts['all'] = array_sum($counts);

        $query = Review::query()->with(['business:id,name,slug', 'user:id,name']);

        if ($status !== 'all') {
            $query->where('status', $status);
        }

        $reviews = $query->latest('id')->paginate(20)->withQueryString();

        return view('admin.reviews.index', compact('reviews', 'status', 'counts'));
    }

    public function update(Request $request, Review $review)
    {
        $this->authorizeAdmin();

        $data = $request->validate([
            'status' => ['required', Rule::in(['approved', 'rejected', 'pending'])],
        ]);

        $review->update(['status' => $data['status']]);

        return back()->with('success', 'Review moderation status updated.');
    }
}