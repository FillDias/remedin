<?php

namespace App\Actions;

use App\Models\Medication;

class ArchiveMedication
{
    /**
     * Stop reminding about the medication while keeping its history.
     */
    public function handle(Medication $medication): void
    {
        $medication->update(['is_active' => false]);
    }
}
