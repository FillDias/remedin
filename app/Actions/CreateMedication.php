<?php

namespace App\Actions;

use App\Enums\MedicationForm;
use App\Models\Medication;
use App\Models\User;

class CreateMedication
{
    /**
     * Register a new medication for the user.
     *
     * @param  array<string, mixed>  $data  Validated medication fields.
     */
    public function handle(User $user, array $data): Medication
    {
        if (! MedicationForm::from($data['form'])->tracksStock()) {
            $data['stock_quantity'] = null;
        }

        return $user->medications()->create($data);
    }
}
