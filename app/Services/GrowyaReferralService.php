<?php

namespace App\Services;

use App\Models\User;
use App\Models\Business;
use App\Models\GrowyaReferral;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class GrowyaReferralService
{
    public function code(User $user): string
    {
        return DB::transaction(function () use ($user) {
            $existing = DB::table('growya_referral_codes')->where('user_id', $user->id)->first();
            if ($existing) return $existing->code;
            // User-id based unique code avoids collisions and race-related duplicate issuance.
            $code = 'GY' . strtoupper(base_convert((string)$user->id, 10, 36)) . strtoupper(substr(hash('sha256', 'growya-referral-' . $user->id . '-' . config('app.key')), 0, 5));
            DB::table('growya_referral_codes')->insertOrIgnore(['user_id' => $user->id, 'code' => $code, 'created_at' => now(), 'updated_at' => now()]);
            return DB::table('growya_referral_codes')->where('user_id', $user->id)->value('code');
        });
    }
    public function register(User $user, ?string $code): void
    {
        $code = strtoupper(trim((string)$code));
        if (!$code) return;
        $owner = DB::table('growya_referral_codes')->where('code', $code)->first();
        if (!$owner || (int)$owner->user_id === (int)$user->id) throw ValidationException::withMessages(['referral_code' => 'Invalid referral code.']);
        $enabled = DB::table('growya_referral_settings')->where('id', 1)->value('enabled');
        if (!$enabled) throw ValidationException::withMessages(['referral_code' => 'Referral program is currently unavailable.']);
        $referral = GrowyaReferral::create(['referrer_id' => $owner->user_id, 'referred_user_id' => $user->id, 'referral_code' => $code]);
        $this->credit($referral, 'customer', null);
    }
    public function credit(GrowyaReferral $referral, string $type, ?Business $business): bool
    {
        if (!in_array($type, ['customer', 'business'], true)) return false;
        return DB::transaction(function () use ($referral, $type, $business) {
            $r = GrowyaReferral::query()->whereKey($referral->id)->lockForUpdate()->firstOrFail();
            if ($r->{$type . '_rewarded_at'}) return false;
            $settings = DB::table('growya_referral_settings')->where('id', 1)->first();
            if (!$settings || !$settings->enabled) return false;
            if ($type === 'business' && (!$business || (int)$business->owner_id !== (int)$r->referred_user_id || $business->verification_status !== 'approved')) return false;
            $points = (int)$settings->{$type . '_points'};
            if ($points > 0) DB::table('growya_reward_transactions')->insert(['user_id' => $r->referrer_id, 'referral_id' => $r->id, 'reward_type' => $type, 'points' => $points, 'description' => $type === 'customer' ? 'Qualified new customer registration' : 'Approved referred-user business', 'created_at' => now(), 'updated_at' => now()]);
            $updates = [$type . '_rewarded_at' => now(), 'updated_at' => now()];
            if ($type === 'business') $updates['rewarded_business_id'] = $business->id;
            DB::table('growya_referrals')->where('id', $r->id)->update($updates);
            return true;
        }, 3);
    }
    public function businessApproved(Business $business): void
    {
        if ($business->verification_status !== 'approved') return;
        $r = GrowyaReferral::where('referred_user_id', $business->owner_id)->first();
        if (!$r) return;
        $credited = $this->credit($r, 'business', $business);
        if ($credited) {
            try {
                app(GrowyaNotificationService::class)->send([(int)$r->referrer_id], 'referral_reward', 'Business referral reward', 'A referred user’s business was approved. Your reward points were recorded.', '/my-referrals');
            } catch (\Throwable $e) {
                report($e);
            }
        }
    }
}
