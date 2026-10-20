<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Fortify\CreateNewUser;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\UserDevice;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Contracts\TwoFactorAuthenticationProvider;
use Laravel\Sanctum\PersonalAccessToken;

class AuthController extends Controller
{
    private function issue(User $user, string $device): array
    {
        return ['token' => $user->createToken($device, ['growya'], now()->addDays(30))->plainTextToken, 'token_type' => 'Bearer', 'expires_in' => 2592000, 'user' => $user->only(['id', 'name', 'email', 'phone', 'user_type', 'status'])];
    }

    public function register(Request $request, CreateNewUser $creator)
    {
        $request->validate(['device_name' => ['nullable', 'string', 'max:100']]);
        $user = $creator->create($request->all());

        return response()->json(['data' => $this->issue($user, $request->input('device_name', 'mobile')), 'next' => $user->user_type === 'owner' ? '/api/v1/businesses/create' : '/api/v1/dashboard'], 201);
    }

    public function login(Request $request)
    {
        $data = $request->validate(['email' => ['required', 'email'], 'password' => ['required', 'string'], 'device_name' => ['required', 'string', 'max:100'], 'two_factor_code' => ['nullable', 'string', 'max:20']]);
        $user = User::where('email', $data['email'])->first();
        if (! $user || ! Hash::check($data['password'], $user->password) || $user->status !== 'active') {
            throw ValidationException::withMessages(['email' => 'Invalid credentials or inactive account.']);
        }
        if ($user->two_factor_secret && $user->two_factor_confirmed_at) {
            if (empty($data['two_factor_code']) || ! app(TwoFactorAuthenticationProvider::class)->verify(decrypt($user->two_factor_secret), $data['two_factor_code'])) {
                throw ValidationException::withMessages(['two_factor_code' => 'A valid authenticator code is required.']);
            }
        }
        $user->forceFill(['last_login_at' => now()])->save();

        return response()->json(['data' => $this->issue($user, $data['device_name'])]);
    }

    public function me(Request $request)
    {
        return response()->json(['data' => $request->user()->only(['id', 'name', 'email', 'phone', 'user_type', 'status'])]);
    }

    public function logout(Request $request)
    {
        $token = $request->user()->currentAccessToken();
        if ($token instanceof PersonalAccessToken) {
            UserDevice::where('user_id', $request->user()->id)->where('session_token_id', $token->id)->update(['is_active' => false]);
            $token->delete();
        }

        return response()->json(['message' => 'Current token revoked.']);
    }
}
