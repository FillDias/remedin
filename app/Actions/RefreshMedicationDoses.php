<?php

namespace App\Actions;

use App\Enums\DoseStatus;
use App\Enums\ReminderStatus;
use App\Models\Medication;
use App\Models\Reminder;
use Illuminate\Support\Facades\DB;

/**
 * Rebuild the upcoming doses of a medication after its schedules or details change.
 *
 * Only future, still pending doses are touched. Past doses and answered doses are history and never change.
 */
class RefreshMedicationDoses
{
    public function __construct(private GenerateDoses $generateDoses) {}

    public function handle(Medication $medication): void
    {
        DB::transaction(function () use ($medication) {
            $medication->doses()
                ->where('status', DoseStatus::Pending)
                ->where('scheduled_for', '>', now())
                ->delete();

            Reminder::query()
                ->where('user_id', $medication->user_id)
                ->where('status', ReminderStatus::Pending)
                ->where('scheduled_for', '>', now())
                ->whereDoesntHave('doses')
                ->delete();

            if ($medication->is_active) {
                $this->generateDoses->forMedication($medication);
            }
        });
    }
}
