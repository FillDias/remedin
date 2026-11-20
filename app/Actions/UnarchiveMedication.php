<?php

namespace App\Actions;

use App\Models\Medication;

class UnarchiveMedication
{
    public function __construct(private RefreshMedicationDoses $refreshMedicationDoses) {}

    /**
     * Start reminding about an archived medication again, with its existing schedules.
     */
    public function handle(Medication $medication): void
    {
        $medication->update(['is_active' => true]);

        $this->refreshMedicationDoses->handle($medication);
    }
}
