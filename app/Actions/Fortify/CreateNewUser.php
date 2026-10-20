<?php

namespace App\Actions\Fortify;

use App\Actions\Teams\CreateTeam;
use App\Concerns\PasswordValidationRules;
use App\Concerns\ProfileValidationRules;
use App\Models\User;
use App\Services\GrowyaReferralService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Laravel\Fortify\Contracts\CreatesNewUsers;
use Spatie\Permission\Models\Role;

class CreateNewUser implements CreatesNewUsers
{
    use PasswordValidationRules, ProfileValidationRules;

    public function __construct(private CreateTeam $createTeam)
    {
        //
    }

    /**
     * Validate and create a newly registered user.
     *
     * @param  array<string, string>  $input
     */
    public function create(array $input): User
    {
        $input['referral_code'] = strtoupper(trim((string) ($input['referral_code'] ?? '')));
        Validator::make($input, [
            ...$this->profileRules(),
            'password' => $this->passwordRules(),
            'account_type' => ['sometimes', Rule::in(['personal', 'business'])],
            'phone' => ['required', 'regex:/^[6-9][0-9]{9}$/', Rule::unique('users', 'phone')],
            'referral_code' => ['nullable', 'string', 'max:20', Rule::exists('growya_referral_codes', 'code')],
        ])->validate();

        return DB::transaction(function () use ($input) {
            $user = User::create([
                'name' => $input['name'],
                'email' => $input['email'],
                'phone' => $input['phone'],
                'user_type' => ($input['account_type'] ?? 'personal') === 'business' ? 'owner' : 'customer',
                'status' => 'active',
                'password' => $input['password'],
            ]);

            Role::firstOrCreate(['name' => 'user', 'guard_name' => 'web']);
            $user->assignRole('user');
            app(GrowyaReferralService::class)->register($user, $input['referral_code'] ?? null);
            $this->createTeam->handle($user, $user->name."'s Team", isPersonal: true);

            return $user;
        });
    }
}
