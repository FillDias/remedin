<?php

use App\Actions\NormalizePhoneNumber;

test('it converts brazilian phone numbers to E.164', function (string $typed) {
    $normalized = (new NormalizePhoneNumber)->handle($typed);

    expect($normalized)->toBe('+5511987654321');
})->with([
    'with mask' => '(11) 98765-4321',
    'digits only' => '11987654321',
    'with country code' => '+55 11 98765-4321',
    'country code without plus' => '5511987654321',
]);

test('it keeps unrecognized input as digits so validation can reject it', function () {
    $normalized = (new NormalizePhoneNumber)->handle('abc 1234');

    expect($normalized)->toBe('+1234');
});
