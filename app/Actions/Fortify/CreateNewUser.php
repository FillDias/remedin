<?php

namespace App\Actions\Fortify;

use App\Actions\NormalizePhoneNumber;
use App\Concerns\PasswordValidationRules;
use App\Concerns\ProfileValidationRules;
use App\Models\User;
use Illuminate\Support\Facades\Validator;
use Laravel\Fortify\Contracts\CreatesNewUsers;

class CreateNewUser implements CreatesNewUsers
{
    use PasswordValidationRules, ProfileValidationRules;

    public function __construct(private NormalizePhoneNumber $normalizePhoneNumber) {}

    /**
     * Validate and create a newly registered user.
     *
     * @param  array<string, string>  $input
     */
    public function create(array $input): User
    {
        if (isset($input['phone'])) {
            $input['phone'] = $this->normalizePhoneNumber->handle($input['phone']);
        }

        Validator::make($input, [
            ...$this->profileRules(),
            'phone' => $this->phoneRules(),
            'password' => $this->passwordRules(),
            'privacy_consent' => ['accepted'],
        ], [
            ...$this->phoneMessages(),
            'privacy_consent.accepted' => __('You must accept the privacy terms to create an account.'),
        ])->validate();

        return User::create([
            'name' => $input['name'],
            'email' => $input['email'],
            'phone' => $input['phone'],
            'password' => $input['password'],
            'privacy_consent_at' => now(),
        ]);
    }
}
