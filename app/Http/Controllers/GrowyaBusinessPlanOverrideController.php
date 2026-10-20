<?php

namespace App\Http\Controllers;

use App\Models\Business;
use App\Services\GrowyaPlanLimitService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class GrowyaBusinessPlanOverrideController extends Controller
{
    private function guard(): void
    {
        abort_unless(auth()->user()?->hasRole('super_admin', 'web'), 403);
    }
    public function edit(Business $business, GrowyaPlanLimitService $limits)
    {
        $this->guard();
        $overrides = DB::table('growya_business_plan_overrides')->where('business_id', $business->id)->get()->keyBy('feature_key');
        $audits = DB::table('growya_plan_override_audits')->where('business_id', $business->id)->latest('id')->limit(30)->get();
        $usage = $limits->snapshot($business);
        return view('growya-entitlements.overrides', compact('business', 'overrides', 'audits', 'usage'));
    }
    public function save(Request $request, Business $business)
    {
        $this->guard();
        $data = $request->validate([
            'feature_key' => ['required', Rule::in(array_keys(GrowyaPlanLimitService::KEYS))],
            'value' => ['required', 'integer', 'min:-1', 'max:1000000'],
            'reason' => ['required', 'string', 'min:5', 'max:1000'],
            'expires_at' => ['nullable', 'date', 'after:now'],
        ]);
        if ($data['feature_key'] === 'featured_listing' && !in_array((int)$data['value'], [0, 1], true)) {
            return back()->withErrors(['value' => 'Featured eligibility must be 0 or 1.'])->withInput();
        }
        DB::transaction(function () use ($data, $business) {
            Business::whereKey($business->id)->lockForUpdate()->firstOrFail();
            $old = DB::table('growya_business_plan_overrides')->where('business_id', $business->id)
                ->where('feature_key', $data['feature_key'])->lockForUpdate()->first();
            DB::table('growya_business_plan_overrides')->updateOrInsert(
                ['business_id' => $business->id, 'feature_key' => $data['feature_key']],
                [
                    'value' => (string)$data['value'],
                    'reason' => $data['reason'],
                    'updated_by' => auth()->id(),
                    'expires_at' => $data['expires_at'] ?? null,
                    'updated_at' => now(),
                    'created_at' => $old?->created_at ?? now()
                ]
            );
            DB::table('growya_plan_override_audits')->insert([
                'business_id' => $business->id,
                'feature_key' => $data['feature_key'],
                'old_value' => $old?->value,
                'new_value' => (string)$data['value'],
                'actor_id' => auth()->id(),
                'reason' => $data['reason'],
                'created_at' => now(),
                'updated_at' => now()
            ]);
        });
        return back()->with('success', 'Override saved.');
    }
    public function remove(Request $request, Business $business, string $key)
    {
        $this->guard();
        abort_unless(array_key_exists($key, GrowyaPlanLimitService::KEYS), 404);
        $data = $request->validate(['reason' => ['required', 'string', 'min:5', 'max:1000']]);
        DB::transaction(function () use ($business, $key, $data) {
            Business::whereKey($business->id)->lockForUpdate()->firstOrFail();
            $old = DB::table('growya_business_plan_overrides')->where('business_id', $business->id)->where('feature_key', $key)->lockForUpdate()->first();
            if (!$old) return;
            DB::table('growya_business_plan_overrides')->where('id', $old->id)->delete();
            DB::table('growya_plan_override_audits')->insert([
                'business_id' => $business->id,
                'feature_key' => $key,
                'old_value' => $old->value,
                'new_value' => null,
                'actor_id' => auth()->id(),
                'reason' => $data['reason'],
                'created_at' => now(),
                'updated_at' => now()
            ]);
        });
        return back()->with('success', 'Override removed.');
    }
}
