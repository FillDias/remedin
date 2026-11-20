<?php

namespace App\Actions;

use App\Models\Medication;
use App\Models\MedicationSchedule;
use Illuminate\Validation\ValidationException;

class AddMedicationSchedule
{
    public function __construct(private RefreshMedicationDoses $refreshMedicationDoses) {}

    /**
     * Add a reminder time to the medication.
     *
     * @param  array{time: string, days_of_week: array<int, int>|null, dose_amount: string|null}  $data
     *
     * @throws ValidationException When the medication already has an active reminder at this time.
     */
    public function handle(Medication $medication, array $data): MedicationSchedule
    {
        $time = substr($data['time'], 0, 5).':00';

        $alreadyExists = $medication->schedules()
            ->where('is_active', true)
            ->where('time', $time)
            ->exists();

        if ($alreadyExists) {
            throw ValidationException::withMessages([
                'time' => __('There is already a reminder at :time.', ['time' => substr($time, 0, 5)]),
            ]);
        }

        $days = $data['days_of_week'];

        if ($days !== null) {
            $days = collect($days)->map(fn ($day) => (int) $day)->unique()->sort()->values()->all();
        }

        $schedule = $medication->schedules()->create([
            'time' => $time,
            'days_of_week' => $days === null || count($days) === 7 ? null : $days,
            'dose_amount' => $data['dose_amount'],
            'is_active' => true,
        ]);

        $this->refreshMedicationDoses->handle($medication);

        return $schedule;
    }
}
