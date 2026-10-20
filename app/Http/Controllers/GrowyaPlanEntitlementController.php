<?php

namespace App\Http\Controllers;

use App\Models\Business;
use App\Models\Feature;
use App\Models\Plan;
use App\Models\PlanFeature;
use App\Services\BusinessAccessService;
use App\Services\GrowyaPlanLimitService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class GrowyaPlanEntitlementController extends Controller
{
    public function edit(Plan $plan)
    {
        abort_unless(auth()->user()?->hasRole('super_admin', 'web'), 403);
        $existing = DB::table('plan_features as pf')->join('features as f', 'f.id', '=', 'pf.feature_id')
            ->where('pf.plan_id', $plan->id)->pluck('pf.value', 'f.key_name');

        return view('growya-entitlements.edit', compact('plan', 'existing'));
    }

    public function update(Request $request, Plan $plan)
    {
        abort_unless(auth()->user()?->hasRole('super_admin', 'web'), 403);
        $rules = [];
        foreach (GrowyaPlanLimitService::KEYS as $key => $label) {
            $rules[$key] = $key === 'featured_listing' ? ['required', Rule::in(['0', '1'])] : ['required', 'integer', 'min:-1', 'max:1000000'];
        }
        $values = $request->validate($rules);
        DB::transaction(function () use ($plan, $values) {
            Plan::whereKey($plan->id)->lockForUpdate()->firstOrFail();
            foreach ($values as $key => $value) {
                $feature = Feature::firstOrCreate(['key_name' => $key], ['name' => GrowyaPlanLimitService::KEYS[$key], 'data_type' => $key === 'featured_listing' ? 'boolean' : 'integer']);
                PlanFeature::updateOrCreate(['plan_id' => $plan->id, 'feature_id' => $feature->id], ['value' => (string) $value]);
            }
        });

        return back()->with('success', 'Plan feature limits saved. Existing items are not deleted.');
    }

    public function usage(Business $business, GrowyaPlanLimitService $limits)
    {
        $user = auth()->user();
        abort_unless($user && app(BusinessAccessService::class)->can($user, $business, 'subscriptions.view'), 403);
        $active = $limits->activeSubscription($business);
        $usage = $limits->snapshot($business);

        return view('growya-entitlements.usage', compact('business','active','usage'));
    }
}
