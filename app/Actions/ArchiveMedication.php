<?php

namespace App\Actions;

use App\Models\Medication;

class ArchiveMedication
{
    public function __construct(private RefreshMedicationDoses $refreshMedicationDoses) {}

    /**
     * Stop reminding about the medication while keeping its history.
     */
    public function handle(Medication $medication): void
    {
        $medication->update(['is_active' => false]);

        $this->refreshMedicationDoses->handle($medication);
    }
}
