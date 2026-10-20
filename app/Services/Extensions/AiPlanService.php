<?php

namespace App\Services\Extensions;

use App\Models\AiPlanRequest;
use Illuminate\Validation\ValidationException;

class AiPlanService
{
    public function active(int $businessId)
    {
        return AiPlanRequest::where('business_id', $businessId)->where('status', 'approved')
            ->where('expires_at', '>', now())->orderBy('expires_at')->get();
    }

    public function entitlements(int $businessId): array
    {
        $packs = $this->active($businessId);

        return [
            'available_credits' => $packs->sum(fn ($p) => max(0, $p->plan_snapshot['credits'] - $p->used_credits)),
            'max_prompts' => $packs->max(fn ($p) => $p->plan_snapshot['max_prompts']) ?? 0,
            'max_references' => $packs->max(fn ($p) => $p->plan_snapshot['max_references']) ?? 0,
            'packs' => $packs,
        ];
    }

    public function pack(int $businessId, ?int $id, string $type): AiPlanRequest
    {
        $packs = $this->active($businessId);
        $pack = $id ? $packs->firstWhere('id', $id) : $packs->first(fn ($p) => ! empty($p->plan_snapshot[$type.'_model']) &&
            $p->plan_snapshot[$type.'_credits'] <= $p->plan_snapshot['credits'] - $p->used_credits);
        if (! $pack) {
            throw ValidationException::withMessages(['plan_request_id' => 'Select an approved, unexpired AI package with sufficient credits.']);
        }
        $s = $pack->plan_snapshot;
        if (empty($s[$type.'_model'])) {
            throw ValidationException::withMessages(['generation_type' => 'This package does not include this generation type.']);
        }
        if ($s[$type.'_credits'] > $s['credits'] - $pack->used_credits) {
            throw ValidationException::withMessages(['credits' => 'This package has insufficient credits. Request another package.']);
        }

        return $pack;
    }
}
