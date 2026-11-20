<?php

namespace App\Policies;

use App\Models\Medication;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class MedicationPolicy
{
    /**
     * Determine whether the user can list their medications.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the medication.
     */
    public function view(User $user, Medication $medication): Response
    {
        return $this->ownedBy($user, $medication);
    }

    /**
     * Determine whether the user can register a new medication.
     */
    public function create(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can edit or archive the medication.
     */
    public function update(User $user, Medication $medication): Response
    {
        return $this->ownedBy($user, $medication);
    }

    /**
     * Determine whether the user can delete the medication.
     */
    public function delete(User $user, Medication $medication): Response
    {
        return $this->ownedBy($user, $medication);
    }

    /**
     * Allow only the owner. Others get a 404 so they cannot learn that the medication exists.
     */
    private function ownedBy(User $user, Medication $medication): Response
    {
        return $user->id === $medication->user_id
            ? Response::allow()
            : Response::denyAsNotFound();
    }
}
