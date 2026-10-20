<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\City;
use App\Models\Country;
use App\Models\State;
use App\Models\User;
use App\Models\UserDevice;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class AccountController extends Controller
{
    private function admin(Request $request): void
    {
        abort_unless($request->user()->hasRole('super_admin', 'web'), 403);
    }

    public function users(Request $request)
    {
        $this->admin($request);
        $data = $request->validate(['search' => ['nullable', 'string', 'max:100']]);

        return response()->json(['data' => User::select('id', 'name', 'email', 'phone', 'user_type', 'status')->when($data['search'] ?? null, fn ($q, $term) => $q->where(fn ($q) => $q->where('name', 'like', '%'.$term.'%')->orWhere('email', 'like', '%'.$term.'%')))->latest('id')->paginate(20)]);
    }

    public function createUser(Request $request)
    {
        $this->admin($request);
        $data = $request->validate(['name' => ['required', 'string', 'max:255'], 'email' => ['required', 'email', Rule::unique('users', 'email')], 'phone' => ['nullable', 'regex:/^[6-9][0-9]{9}$/', Rule::unique('users', 'phone')], 'password' => ['required', 'string', 'min:12', 'confirmed']]);
        $user = User::create(collect($data)->except('password_confirmation')->all() + ['user_type' => 'customer', 'status' => 'active']);

        return response()->json(['data' => $user->only('id', 'name', 'email', 'phone', 'status')], 201);
    }

    public function updateUser(Request $request, User $user)
    {
        $this->admin($request);
        abort_if($user->hasRole('super_admin', 'web'), 403, 'Super admin account is protected.');
        $data = $request->validate(['name' => ['sometimes', 'required', 'string', 'max:255'], 'email' => ['sometimes', 'required', 'email', Rule::unique('users', 'email')->ignore($user->id)], 'status' => ['sometimes', Rule::in(['active', 'inactive'])]]);
        $user->update($data);

        return response()->json(['data' => $user->only('id', 'name', 'email', 'phone', 'status')]);
    }

    public function deleteUser(Request $request, User $user)
    {
        $this->admin($request);
        abort_if($user->hasRole('super_admin', 'web') || $user->id === $request->user()->id, 403, 'Protected account.');
        // Retain commerce/customer history and FK relationships. Access is revoked immediately.
        $user->update(['status' => 'inactive']);
        $user->tokens()->delete();
        UserDevice::where('user_id', $user->id)->update(['is_active' => false]);

        return response()->json(['message' => 'Account deactivated; history preserved.']);
    }

    public function profile(Request $request)
    {
        $user = $request->user();
        $data = $request->validate(['name' => ['sometimes', 'required', 'string', 'max:255'], 'email' => ['sometimes', 'required', 'email', Rule::unique('users', 'email')->ignore($user->id)], 'phone' => ['sometimes', 'nullable', 'regex:/^[6-9][0-9]{9}$/', Rule::unique('users', 'phone')->ignore($user->id)]]);
        if (isset($data['email']) && $data['email'] !== $user->email) {
            $user->forceFill(['email_verified_at' => null]);
        }
        if (array_key_exists('phone', $data) && $data['phone'] !== $user->phone) {
            $user->forceFill(['phone_verified_at' => null]);
        }
        $user->fill($data)->save();

        return response()->json(['data' => $user->only('id', 'name', 'email', 'phone', 'user_type', 'status')]);
    }

    public function password(Request $request)
    {
        $data = $request->validate(['current_password' => ['required', 'string'], 'password' => ['required', 'string', 'min:12', 'confirmed']]);
        if (! Hash::check($data['current_password'], $request->user()->password)) {
            throw ValidationException::withMessages(['current_password' => 'Current password is incorrect.']);
        }
        $request->user()->update(['password' => $data['password']]);
        $request->user()->tokens()->delete();
        UserDevice::where('user_id', $request->user()->id)->update(['is_active' => false]);

        return response()->json(['message' => 'Password updated. All API tokens revoked; sign in again.']);
    }

    public function locations(Request $request, string $kind)
    {
        $data = $request->validate(['country_id' => ['nullable', 'integer', 'min:1'], 'state_id' => ['nullable', 'integer', 'min:1'], 'search' => ['nullable', 'string', 'max:100']]);
        $model = match ($kind) {
            'countries' => Country::class,'states' => State::class,'cities' => City::class,default => abort(404)
        };
        $query = $model::where('is_active', true);
        if ($kind === 'states' && ! empty($data['country_id'])) {
            $query->where('country_id', $data['country_id']);
        }
        if ($kind === 'cities' && ! empty($data['state_id'])) {
            $query->where('state_id', $data['state_id']);
        }
        if (! empty($data['search'])) {
            $query->where('name', 'like', '%'.$data['search'].'%');
        }

        return response()->json(['data' => $query->orderBy('name')->paginate(100)]);
    }
}
