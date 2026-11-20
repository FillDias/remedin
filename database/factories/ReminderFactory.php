<?php

namespace Database\Factories;

use App\Enums\ReminderStatus;
use App\Models\Reminder;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Reminder>
 */
class ReminderFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'scheduled_for' => now()->addHour()->startOfHour(),
            'status' => ReminderStatus::Pending,
        ];
    }
}
