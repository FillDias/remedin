<?php

namespace Database\Factories;

use App\Enums\MealRelation;
use App\Enums\MedicationForm;
use App\Models\Medication;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Medication>
 */
class MedicationFactory extends Factory
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
            'name' => fake()->randomElement(['Lavitan A-Z', 'Vitamina D3', 'Losartana', 'Ômega 3']),
            'strength' => fake()->randomElement([null, '500 mg', '2.000 UI']),
            'form' => MedicationForm::Tablet,
            'dose_amount' => 1,
            'dose_unit' => 'comprimido',
            'meal_relation' => MealRelation::Any,
            'stock_quantity' => 30,
            'low_stock_days' => 5,
            'starts_on' => today(),
            'ends_on' => null,
            'notes' => null,
            'is_active' => true,
        ];
    }

    /**
     * Indicate that the medication has been archived.
     */
    public function archived(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * Indicate that the medication is measured in drops, without stock control.
     */
    public function drops(): static
    {
        return $this->state(fn (array $attributes) => [
            'form' => MedicationForm::Drops,
            'dose_amount' => 20,
            'dose_unit' => 'gotas',
            'stock_quantity' => null,
        ]);
    }
}
