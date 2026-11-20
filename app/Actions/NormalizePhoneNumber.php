<?php

namespace App\Actions;

class NormalizePhoneNumber
{
    /**
     * Pattern for a Brazilian mobile number in E.164 format (+55 DDD 9XXXXXXXX).
     */
    public const BRAZILIAN_MOBILE_PATTERN = '/^\+55[1-9]{2}9\d{8}$/';

    /**
     * Convert a phone number typed by the user to E.164 format.
     *
     * "(11) 98765-4321" and "+55 11 98765-4321" both become "+5511987654321".
     * Invalid input is returned as digits only, so validation can reject it.
     */
    public function handle(string $phone): string
    {
        $digits = preg_replace('/\D/', '', $phone) ?? '';

        if (strlen($digits) === 11) {
            $digits = '55'.$digits;
        }

        return '+'.$digits;
    }
}
