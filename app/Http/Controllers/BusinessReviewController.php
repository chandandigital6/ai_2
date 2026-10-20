<?php

namespace App\Http\Controllers;

use App\Models\Business;
use App\Models\Review;
use App\Services\BusinessAccessService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class BusinessReviewController extends Controller
{
    public function __construct(private BusinessAccessService $access) {}

    private function publicBusiness(string $slug): Business
    {
        return Business::query()->where('slug', $slug)
            ->where('status', 'active')->where('verification_status', 'approved')->firstOrFail();
    }

    public function publicIndex(string $slug)
    {
        $business = $this->publicBusiness($slug);
        $reviews = Review::query()->where('business_id', $business->id)
            ->where('status', 'approved')->with('user:id,name')
            ->latest('id')->paginate(10);
        $summary = Review::query()->where('business_id', $business->id)
            ->where('status', 'approved')->selectRaw('COUNT(*) as total, AVG(rating) as average_rating')->first();
        $myReview = auth()->check() ? Review::query()->where('business_id', $business->id)
            ->where('user_id', auth()->id())->first() : null;

        return view('public.reviews.index', compact('business', 'reviews', 'summary', 'myReview'));
    }

    public function store(Request $request, string $slug)
    {
        abort_unless(auth()->check(), 403);
        $business = $this->publicBusiness($slug);
        abort_if((int) $business->owner_id === (int) auth()->id(), 403, 'You cannot review your own business.');
        $data = $request->validate([
            'rating' => ['required', 'integer', 'between:1,5'],
            'review' => ['required', 'string', 'min:5', 'max:3000'],
        ]);
        DB::transaction(function () use ($business, $data) {
            Business::query()->whereKey($business->id)->lockForUpdate()->firstOrFail();
            if (Review::query()->where('business_id', $business->id)->where('user_id', auth()->id())->exists()) {
                throw ValidationException::withMessages(['review' => 'You have already reviewed this business. Please edit your review.']);
            }
            Review::create([
                'business_id' => $business->id,
                'user_id' => auth()->id(),
                'order_id' => null,
                'rating' => $data['rating'],
                'review' => trim($data['review']),
                'status' => 'pending',
                'helpful_count' => 0,
            ]);
        }, 3);

        return back()->with('success', 'Review submitted for moderation.');
    }

    public function update(Request $request, string $slug, Review $review)
    {
        $business = $this->publicBusiness($slug);
        abort_unless(auth()->check() && (int) $review->business_id === (int) $business->id
            && (int) $review->user_id === (int) auth()->id(), 403);
        $data = $request->validate([
            'rating' => ['required', 'integer', 'between:1,5'],
            'review' => ['required', 'string', 'min:5', 'max:3000'],
        ]);
        $review->update(['rating' => $data['rating'], 'review' => trim($data['review']), 'status' => 'pending']);

        return back()->with('success', 'Review updated and sent for moderation.');
    }

    public function ownerIndex(Business $business)
    {
        abort_unless(auth()->check() && $this->access->can(auth()->user(), $business, 'reviews.view'), 403);
        $reviews = Review::query()->where('business_id', $business->id)
            ->with('user:id,name')->latest('id')->paginate(20);

        return view('businesses.reviews.index', compact('business', 'reviews'));
    }

    public function reply(Request $request, Business $business, Review $review)
    {
        abort_unless(auth()->check() && $this->access->can(auth()->user(), $business, 'reviews.manage'), 403);
        abort_unless((int) $review->business_id === (int) $business->id, 404);
        $data = $request->validate(['business_reply' => ['required', 'string', 'min:2', 'max:2000']]);
        abort_unless($review->status === 'approved', 422, 'Only approved reviews can be replied to.');
        $review->update(['business_reply' => trim($data['business_reply']), 'replied_at' => now()]);

        return back()->with('success', 'Business reply saved.');
    }
}
