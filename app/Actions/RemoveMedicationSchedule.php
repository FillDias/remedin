<?php

namespace App\Actions;

use App\Models\MedicationSchedule;

class RemoveMedicationSchedule
{
    /**
     * Stop reminding at this time.
     *
     * The schedule is deactivated instead of deleted so past doses keep pointing to it.
     */
    public function handle(MedicationSchedule $schedule): void
    {
        $schedule->update(['is_active' => false]);
    }
}
