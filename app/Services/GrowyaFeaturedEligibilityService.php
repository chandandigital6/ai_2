<?php
namespace App\Services;
use App\Models\Business;
use Illuminate\Support\Facades\DB;
class GrowyaFeaturedEligibilityService
{
    public function eligibleIds(): array
    {
        // Narrow the candidates to active, approved businesses with either
        // an active subscription configured for featured placement or an override.
        $feature=DB::table('features')->where('key_name','featured_listing')->value('id');
        $planIds=$feature ? DB::table('plan_features')->where('feature_id',$feature)
            ->where('value','1')->pluck('plan_id')->all() : [];
        $subscriptionBusinessIds=$planIds ? DB::table('subscriptions')
            ->whereIn('plan_id',$planIds)->where('status','active')->where('starts_at','<=',now())
            ->where(fn($q)=>$q->whereNull('ends_at')->orWhere('ends_at','>',now()))
            ->pluck('business_id')->all() : [];
        $overridden=DB::table('growya_business_plan_overrides')->where('feature_key','featured_listing')
            ->where(fn($q)=>$q->whereNull('expires_at')->orWhere('expires_at','>',now()))
            ->pluck('value','business_id')->all();
        $candidates=array_unique(array_merge($subscriptionBusinessIds,array_keys($overridden)));
        if (!$candidates) return [];
        // Re-check effective entitlement; false override must suppress plan eligibility.
        $limits=app(GrowyaPlanLimitService::class);
        return Business::query()->whereIn('id',$candidates)->where('status','active')
            ->where('verification_status','approved')->get(['id','owner_id','status','verification_status'])
            ->filter(fn($b)=>$limits->allowance($b,'featured_listing')===true)
            ->pluck('id')->all();
    }
}
