<?php

namespace App\Policies;

use App\Models\Dose;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class DosePolicy
{
    /**
     * Determine whether the user can answer (take, skip or undo) the dose.
     *
     * Others get a 404 so they cannot learn that the dose exists.
     */
    public function update(User $user, Dose $dose): Response
    {
        return $user->id === $dose->reminder->user_id
            ? Response::allow()
            : Response::denyAsNotFound();
    }
}
