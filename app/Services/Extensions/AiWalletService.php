<?php

namespace App\Services\Extensions;

use App\Models\AiCreditTransaction;
use App\Models\AiCreditWallet;
use App\Models\Business;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AiWalletService
{
    public function balance(int $businessId): array
    {
        $total = (int) AiCreditWallet::where('business_id', $businessId)->sum('total_credits');
        $used = (int) AiCreditWallet::where('business_id', $businessId)->sum('used_credits');

        return ['total_credits' => $total, 'used_credits' => $used, 'available_credits' => max(0, $total - $used)];
    }

    public function change(int $businessId, int $credits, string $type, string $key, ?int $generationId = null): void
    {
        if ($credits < 1 || ! in_array($type, ['grant', 'debit', 'refund'], true)) {
            throw new \InvalidArgumentException('Invalid credit operation.');
        }
        DB::transaction(function () use ($businessId, $credits, $type, $key, $generationId) {
            Business::whereKey($businessId)->lockForUpdate()->firstOrFail();
            $existing = AiCreditTransaction::where('business_id', $businessId)->where('request_key', $key)->first();
            if ($existing) {
                if ((int) $existing->credits !== $credits || $existing->type !== $type) {
                    throw ValidationException::withMessages(['request_key' => 'This key belongs to a different ledger operation.']);
                }

                return;
            }
            $wallets = AiCreditWallet::where('business_id', $businessId)->orderBy('id')->lockForUpdate()->get();
            $total = (int) $wallets->sum('total_credits');
            $used = (int) $wallets->sum('used_credits');
            if ($type === 'debit' && $total - $used < $credits) {
                throw ValidationException::withMessages(['credits' => 'Insufficient AI credits.']);
            }
            if ($type === 'debit') {
                $used += $credits;
            } elseif ($type === 'refund') {
                $used -= $credits;
            } else {
                $total += $credits;
            }
            if ($used < 0 || $total > 2147483647 || $used > 2147483647) {
                throw ValidationException::withMessages(['credits' => 'Invalid wallet balance.']);
            }
            // Preserve old wallet row IDs while canonicalizing their aggregate balance under the business lock.
            $wallet = $wallets->first() ?? AiCreditWallet::create(['business_id' => $businessId, 'total_credits' => 0, 'used_credits' => 0]);
            $wallet->update(['total_credits' => $total, 'used_credits' => $used]);
            foreach ($wallets->skip(1) as $extra) {
                $extra->update(['total_credits' => 0, 'used_credits' => 0]);
            }
            $t = AiCreditTransaction::create(['business_id' => $businessId, 'ai_generation_id' => $generationId, 'type' => $type, 'credits' => $credits, 'balance_after' => $total - $used, 'description' => ucfirst($type).' AI credits']);
            $t->forceFill(['request_key' => $key])->save();
        }, 3);
    }
}
