<?php
namespace App\Services;

use App\Models\Business;
use App\Models\Feature;
use App\Models\PlanFeature;
use App\Models\Subscription;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class GrowyaPlanLimitService
{
    public const KEYS = [
        'max_products' => 'Maximum products',
        'max_services' => 'Maximum services',
        'max_staff' => 'Maximum active staff',
        'max_gallery' => 'Maximum business gallery items',
        'featured_listing' => 'Featured listing enabled',
    ];

    // Intentional policy: no active subscription => restricted free fallback.
    // null quota means unlimited, NOT 0. Edit fallback to match commercial policy.
    public const FALLBACK = [
        'max_products' => 20, 'max_services' => 5,
        'max_staff' => 1, 'max_gallery' => 10, 'featured_listing' => false,
    ];

    public function activeSubscription(Business $business): ?Subscription
    {
        return Subscription::query()->where('business_id', $business->id)
            ->where('status', 'active')->where('starts_at', '<=', now())
            ->where(function ($q) { $q->whereNull('ends_at')->orWhere('ends_at', '>', now()); })
            ->orderByDesc('starts_at')->first();
    }

    public function allowance(Business $business, string $key): int|bool|null
    {
        if (!array_key_exists($key, self::KEYS)) throw new \InvalidArgumentException('Unknown plan feature');
        $override = DB::table('growya_business_plan_overrides')
            ->where('business_id', $business->id)->where('feature_key', $key)
            ->where(function ($q) { $q->whereNull('expires_at')->orWhere('expires_at','>',now()); })
            ->value('value');
        if ($override !== null) {
            if ($key === 'featured_listing') return $override === '1';
            return $override === '-1' ? null : max(0, (int) $override);
        }
        $sub = $this->activeSubscription($business);
        if (!$sub) return self::FALLBACK[$key];
        $raw = DB::table('plan_features as pf')->join('features as f','f.id','=','pf.feature_id')
            ->where('pf.plan_id',$sub->plan_id)->where('f.key_name',$key)->value('pf.value');
        // Unconfigured entitlement stays on the conservative fallback; it is not unlimited.
        if ($raw === null || $raw === '') return self::FALLBACK[$key];
        if ($key === 'featured_listing') return $raw === '1';
        if ($raw === '-1') return null; // -1 is explicit unlimited
        return max(0, (int) $raw);
    }

    public function used(Business $business, string $key): int
    {
        return match ($key) {
            'max_products' => $business->products()->count(),
            'max_services' => $business->services()->count(),
            'max_staff' => $business->businessUsers()->where('is_active',true)->where('role','!=','owner')->count(),
            'max_gallery' => \App\Models\BusinessGallery::where('business_id', $business->id)->count(),
            default => 0,
        };
    }

    // Must run within a DB transaction and after locking businesses.id.
    public function assertCanAddMany(Business $business, string $key, int $additional): void
    {
        if ($additional <= 0) return;
        $limit = $this->allowance($business, $key);
        if ($limit !== null && $this->used($business, $key) + $additional > $limit) {
            throw ValidationException::withMessages(['plan_limit' => 'Subscription limit reached for '.self::KEYS[$key].'. Current usage: '.$this->used($business,$key).', requested: '.$additional.', allowed: '.$limit.'.']);
        }
    }

    public function featuredBusinessIds(): array
    {
        // Only evaluate approved, active businesses; entitlement may come from
        // an unexpired override, active subscription, or free fallback.
        return Business::query()->where('status','active')
            ->where('verification_status','approved')->pluck('id')->all();
    }

    public function assertCanAdd(Business $business, string $key): void
    {
        $limit = $this->allowance($business, $key);
        if ($limit !== null && $this->used($business, $key) >= $limit) {
            throw ValidationException::withMessages(['plan_limit' => 'Subscription limit reached for '.self::KEYS[$key].'. Upgrade or renew your plan.']);
        }
    }

    public function snapshot(Business $business): array
    {
        $rows=[];
        foreach(self::KEYS as $key=>$label) {
            $rows[$key] = ['label'=>$label,'allowed'=>$this->allowance($business,$key), 'used'=>$this->used($business,$key)];
        }
        return $rows;
    }
}
