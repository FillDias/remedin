<?php

namespace App\Actions;

use App\Models\User;

class UpdateUserProfile
{
    /**
     * Update the user's profile, resetting verifications for changed contact data.
     *
     * @param  array{name: string, email: string, phone: string, timezone: string}  $validated
     */
    public function handle(User $user, array $validated): void
    {
        $user->fill($validated);

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        if ($user->isDirty('phone')) {
            $user->phone_verified_at = null;
            $user->whatsapp_opt_in_at = null;
        }

        $user->save();
    }
}
