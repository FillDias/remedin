<?php

namespace Database\Factories;

use App\Enums\DoseStatus;
use App\Models\Dose;
use App\Models\MedicationSchedule;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Dose>
 */
class DoseFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * The medication and the reminder are derived from the schedule, so all of them
     * belong to the same user, like in the real application.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'medication_schedule_id' => MedicationSchedule::factory(),
            'scheduled_for' => now()->addHour()->startOfHour(),
            'medication_id' => fn (array $attributes) => $this->scheduleFrom($attributes)->medication_id,
            'reminder_id' => fn (array $attributes) => $this->scheduleFrom($attributes)
                ->medication->user->reminders()
                ->firstOrCreate(['scheduled_for' => $attributes['scheduled_for']])
                ->id,
            'amount' => 1,
            'status' => DoseStatus::Pending,
        ];
    }

    /**
     * Find the schedule referenced by the dose attributes.
     *
     * @param  array<string, mixed>  $attributes
     */
    private function scheduleFrom(array $attributes): MedicationSchedule
    {
        return MedicationSchedule::query()->whereKey($attributes['medication_schedule_id'])->firstOrFail();
    }

    /**
     * Indicate that the dose was taken.
     */
    public function taken(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => DoseStatus::Taken,
            'taken_at' => $attributes['scheduled_for'],
        ]);
    }
}
