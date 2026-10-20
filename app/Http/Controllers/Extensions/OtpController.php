<?php

namespace App\Http\Controllers\Extensions;

use App\Models\User;
use App\Services\Extensions\SmsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Contracts\TwoFactorAuthenticationProvider;

class OtpController extends ExtensionController
{
    public function send(Request $request, SmsService $sms)
    {
        $d = $request->validate(['phone' => ['required', 'regex:/^[6-9][0-9]{9}$/']]);
        abort_unless($sms->available(), 503, 'SMS service has not been configured.');
        $phone = $d['phone'];
        $key = 'otp-send:'.hash('sha256', $phone);
        abort_if(RateLimiter::tooManyAttempts($key, 1), 429, 'Wait 60 seconds before sending again.');
        RateLimiter::hit($key, 60);
        Cache::lock($key.':lock', 30)->block(3, function () use ($phone, $sms) {
            DB::table('otp_codes')->where('phone', $phone)->where('purpose', 'login')->whereNull('verified_at')->update(['expires_at' => now()]);
            $code = (string) random_int(100000, 999999);
            $id = DB::table('otp_codes')->insertGetId(['phone' => $phone, 'otp' => Hash::make($code), 'purpose' => 'login', 'expires_at' => now()->addMinutes(5), 'attempts' => 0, 'created_at' => now(), 'updated_at' => now()]);
            try {
                $sms->send($phone, $code, 'login');
            } catch (\Throwable $e) {
                DB::table('otp_codes')->where('id', $id)->update(['expires_at' => now()]);
                throw $e;
            }
        });

        // Same send response for registered and unregistered numbers. Verification requires an existing active account.
        return response()->json(['message' => 'If eligible, use the code sent to your phone.', 'expires_in' => 300]);
    }

    public function verify(Request $request)
    {
        $d = $request->validate(['phone' => ['required', 'regex:/^[6-9][0-9]{9}$/'], 'code' => ['required', 'digits:6'], 'device_name' => ['required', 'string', 'max:100'], 'two_factor_code' => ['nullable', 'string', 'max:20']]);
        $key = 'otp-verify:'.hash('sha256', $d['phone']);
        abort_if(RateLimiter::tooManyAttempts($key, 10), 429);
        RateLimiter::hit($key, 600);
        // Commit attempt counters before reporting errors; a thrown validation error must not roll them back.
        $result = DB::transaction(function () use ($d) {
            $otp = DB::table('otp_codes')->where('phone', $d['phone'])->where('purpose', 'login')->latest('id')->lockForUpdate()->first();
            if (! $otp || $otp->verified_at || now()->gte($otp->expires_at) || $otp->attempts >= 5) {
                return null;
            }
            DB::table('otp_codes')->where('id', $otp->id)->increment('attempts');
            if (! Hash::check($d['code'], $otp->otp)) {
                return null;
            }
            $user = User::where('phone', $d['phone'])->where('status', 'active')->first();
            if (! $user) {
                return null;
            }
            if ($user->two_factor_secret && $user->two_factor_confirmed_at && (empty($d['two_factor_code']) || ! app(TwoFactorAuthenticationProvider::class)->verify(decrypt($user->two_factor_secret), $d['two_factor_code']))) {
                return null;
            }
            DB::table('otp_codes')->where('id', $otp->id)->update(['verified_at' => now()]);
            $user->forceFill(['phone_verified_at' => now(), 'last_login_at' => now()])->save();

            return ['token' => $user->createToken($d['device_name'], ['growya'], now()->addDays(30))->plainTextToken, 'token_type' => 'Bearer', 'expires_in' => 2592000, 'user' => $user->only(['id', 'name', 'email', 'phone', 'status', 'user_type'])];
        }, 3);
        if (! $result) {
            throw ValidationException::withMessages(['code' => 'Code invalid, expired, used, or account ineligible. Register first if needed.']);
        }

        return response()->json(['data' => $result]);
    }
}
