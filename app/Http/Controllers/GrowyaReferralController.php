<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\GrowyaReferral;
use App\Services\GrowyaReferralService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class GrowyaReferralController extends Controller
{
    public function mine(Request $request, GrowyaReferralService $service)
    {
        $user = $request->user();
        $code = $service->code($user);
        $records = GrowyaReferral::with('referredUser:id,name,created_at')->where('referrer_id', $user->id)->latest()->paginate(15);
        $points = DB::table('growya_reward_transactions')->where('user_id', $user->id)->sum('points');
        $activity = DB::table('growya_reward_transactions')->where('user_id', $user->id)->latest('id')->limit(30)->get();
        return view('growya-referrals.mine', compact('code', 'records', 'points', 'activity'));
    }
    private function adminOnly(Request $request): void
    {
        abort_unless($request->user()?->hasRole('super_admin', 'web'), 403);
    }
    public function admin(Request $request)
    {
        $this->adminOnly($request);
        $referrals = GrowyaReferral::with(['referrer:id,name', 'referredUser:id,name'])->latest()->paginate(25);
        $points = DB::table('growya_reward_transactions')->sum('points');
        $users = DB::table('growya_referrals')->count();
        $businesses = DB::table('growya_referrals')->whereNotNull('business_rewarded_at')->count();
        $settings = DB::table('growya_referral_settings')->where('id', 1)->first();
        return view('growya-referrals.admin', compact('referrals', 'points', 'users', 'businesses', 'settings'));
    }
    public function settings(Request $request)
    {
        $this->adminOnly($request);
        $data = $request->validate(['customer_points' => ['required', 'integer', 'between:0,100000'], 'business_points' => ['required', 'integer', 'between:0,100000'], 'enabled' => ['required', 'boolean']]);
        DB::table('growya_referral_settings')->updateOrInsert(['id' => 1], [...$data, 'updated_at' => now()]);
        return back()->with('success', 'Referral rules updated.');
    }


  public function approveBusinessReward(
    Request $request,
    GrowyaReferral $referral,
    GrowyaReferralService $service
) {
    $this->adminOnly($request);

    if ($referral->business_rewarded_at) {
        return back()->with(
            'success',
            'Business referral reward already credited.'
        );
    }

    // Referral se register hue user ka
    // approved business find karo.
    $business = \App\Models\Business::query()
        ->where('owner_id', $referral->referred_user_id)
        ->where('status', 'active')
        ->where('verification_status', 'approved')
        ->orderBy('id')
        ->first();

    if (!$business) {
        return back()->withErrors([
            'business' =>
                'Referred user ka koi approved business nahi mila. Pehle Business Verification se approve karein.'
        ]);
    }

    // Existing service ka secured credit method.
    $credited = $service->credit(
        $referral,
        'business',
        $business
    );

    if (!$credited) {
        return back()->withErrors([
            'business' =>
                'Reward credit nahi hua. Referral program settings aur previous reward check karein.'
        ]);
    }

    return back()->with(
        'success',
        'Business referral reward approved and credited successfully.'
    );
}
}
