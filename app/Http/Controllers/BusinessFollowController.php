<?php

namespace App\Http\Controllers;

use App\Models\Business;
use App\Models\BusinessFollower;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class BusinessFollowController extends Controller
{
    public function index(Request $request): View
    {
        $following = BusinessFollower::query()
            ->where('user_id', $request->user()->id)
            ->whereHas('business', fn ($q) => $q
                ->where('status', 'active')
                ->where('verification_status', 'approved'))
            ->with(['business:id,name,slug,logo,cover,description,status,verification_status,followers_count'])
            ->latest('id')
            ->paginate(12);

        return view('public.following.index', compact('following'));
    }

    public function store(Request $request, Business $business): RedirectResponse
    {
        abort_unless($business->status === 'active'
            && $business->verification_status === 'approved', 404);

        DB::transaction(function () use ($request, $business) {
            // Serialize changes for this business to keep the cached count accurate.
            $locked = Business::query()->whereKey($business->id)
                ->lockForUpdate()->firstOrFail();

            abort_unless($locked->status === 'active'
                && $locked->verification_status === 'approved', 404);

            BusinessFollower::query()->firstOrCreate(
                [
                    'business_id' => $locked->id,
                    'user_id' => $request->user()->id,
                ],
                [
                    'notifications_enabled' => false,
                    'followed_at' => now(),
                ]
            );

            $locked->update([
                'followers_count' => BusinessFollower::query()
                    ->where('business_id', $locked->id)->count(),
            ]);
        }, 3);

        return back()->with('success', 'Business followed successfully.');
    }

    public function destroy(Request $request, Business $business): RedirectResponse
    {
        // Allow unfollow even when a business has been deactivated/rejected.
        DB::transaction(function () use ($request, $business) {
            $locked = Business::query()->whereKey($business->id)
                ->lockForUpdate()->firstOrFail();

            BusinessFollower::query()
                ->where('business_id', $locked->id)
                ->where('user_id', $request->user()->id)
                ->delete();

            $locked->update([
                'followers_count' => BusinessFollower::query()
                    ->where('business_id', $locked->id)->count(),
            ]);
        }, 3);

        return back()->with('success', 'Business unfollowed successfully.');
    }
}
