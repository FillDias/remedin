<?php

namespace Database\Factories;

use App\Models\Medication;
use App\Models\MedicationSchedule;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MedicationSchedule>
 */
class MedicationScheduleFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'medication_id' => Medication::factory(),
            'time' => '08:00:00',
            'days_of_week' => null,
            'dose_amount' => null,
            'is_active' => true,
        ];
    }

    /**
     * Indicate that the schedule only runs on the given ISO days of the week (1 = Monday).
     *
     * @param  array<int, int>  $days
     */
    public function onDays(array $days): static
    {
        return $this->state(fn (array $attributes) => [
            'days_of_week' => $days,
        ]);
    }
}
