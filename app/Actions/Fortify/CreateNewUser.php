<?php

namespace App\Actions\Fortify;

use App\Concerns\PasswordValidationRules;
use App\Concerns\ProfileValidationRules;
use App\Models\User;
use Illuminate\Support\Facades\Validator;
use Laravel\Fortify\Contracts\CreatesNewUsers;

class CreateNewUser implements CreatesNewUsers
{
    use PasswordValidationRules, ProfileValidationRules;

    /**
     * Validate and create a newly registered user.
     *
     * @param  array<string, string>  $input
     */
    public function create(array $input): User
    {
        Validator::make($input, [
            ...$this->profileRules(),
            'password' => $this->passwordRules(),
            'privacy_accepted' => ['required', 'accepted'],
        ], [
            'privacy_accepted.accepted' => 'Vous devez accepter la politique de confidentialité pour créer un compte.',
            'privacy_accepted.required' => 'Vous devez accepter la politique de confidentialité pour créer un compte.',
        ])->validate();

        return User::create([
            'name' => $input['name'],
            'email' => $input['email'],
            'password' => $input['password'],
            'privacy_accepted_at' => now(),
            'privacy_policy_version' => config('privacy.version'),
        ]);
    }
}
