<?php

namespace App\Concerns;

use App\Actions\NormalizePhoneNumber;
use App\Enums\BrazilianTimezone;
use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;
use Illuminate\Validation\Rules\Unique;

trait ProfileValidationRules
{
    /**
     * Get the validation rules used to validate user profiles.
     *
     * @return array<string, array<int, Unique|ValidationRule|array<mixed>|string>>
     */
    protected function profileRules(?int $userId = null): array
    {
        return [
            'name' => $this->nameRules(),
            'email' => $this->emailRules($userId),
        ];
    }

    /**
     * Get the validation rules used to validate user names.
     *
     * @return array<int, Unique|ValidationRule|array<mixed>|string>
     */
    protected function nameRules(): array
    {
        return ['required', 'string', 'max:255'];
    }

    /**
     * Get the validation rules used to validate a WhatsApp phone number in E.164 format.
     *
     * @return array<int, Unique|ValidationRule|array<mixed>|string>
     */
    protected function phoneRules(?int $userId = null): array
    {
        return [
            'required',
            'string',
            'regex:'.NormalizePhoneNumber::BRAZILIAN_MOBILE_PATTERN,
            $userId === null
                ? Rule::unique(User::class)
                : Rule::unique(User::class)->ignore($userId),
        ];
    }

    /**
     * Get the validation rules used to validate the user's time zone.
     *
     * @return array<int, Enum|string>
     */
    protected function timezoneRules(): array
    {
        return ['required', Rule::enum(BrazilianTimezone::class)];
    }

    /**
     * Get the custom validation messages for the phone number.
     *
     * @return array<string, string>
     */
    protected function phoneMessages(): array
    {
        return [
            'phone.regex' => __('Enter a mobile number with area code, e.g. (11) 98765-4321.'),
        ];
    }

    /**
     * Get the validation rules used to validate user emails.
     *
     * @return array<int, Unique|ValidationRule|array<mixed>|string>
     */
    protected function emailRules(?int $userId = null): array
    {
        return [
            'required',
            'string',
            'email',
            'max:255',
            $userId === null
                ? Rule::unique(User::class)
                : Rule::unique(User::class)->ignore($userId),
        ];
    }
}
