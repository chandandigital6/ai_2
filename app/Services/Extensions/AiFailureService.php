<?php

namespace App\Services\Extensions;

use App\Exceptions\AiProviderException;
use App\Models\AiGeneration;
use App\Models\AiPlanRequest;
use App\Models\Business;
use Illuminate\Support\Facades\DB;

class AiFailureService
{
    public function fail(int $id, ?\Throwable $error = null, bool $onlyStale = false): void
    {
        $businessId = AiGeneration::whereKey($id)->value('business_id');
        if (! $businessId) {
            return;
        }
        DB::transaction(function () use ($id, $businessId, $error, $onlyStale) {
            // Same lock order as generation and approval: business -> generation -> package.
            Business::whereKey($businessId)->lockForUpdate()->firstOrFail();
            $g = AiGeneration::whereKey($id)->lockForUpdate()->first();
            if (! $g || in_array($g->status, ['completed', 'failed'], true)) {
                return;
            }
            if ($onlyStale && (($g->status === 'processing' && $g->updated_at->greaterThanOrEqualTo(now()->subMinutes(6))) ||
                ($g->status === 'queued' && $g->created_at->greaterThanOrEqualTo(now()->subMinutes(30))))) {
                return;
            }
            if ($g->ai_plan_request_id) {
                $pack = AiPlanRequest::whereKey($g->ai_plan_request_id)->lockForUpdate()->firstOrFail();
                $pack->update(['used_credits' => max(0, $pack->used_credits - $g->credits_used)]);
            }
            app(AiWalletService::class)->change($businessId, $g->credits_used, 'refund', 'refund:'.$g->id, $g->id);
            $g->update(['status' => 'failed', 'error_code' => $error instanceof AiProviderException ? $error->errorCode : 'generation_failed',
                'error' => ($error instanceof AiProviderException ? $error->getMessage() : 'Generation could not finish. Try again or contact the administrator.').' Credits refunded.']);
        }, 3);
    }
}
