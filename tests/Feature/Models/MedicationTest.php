<?php

use App\Enums\MedicationForm;
use App\Models\Medication;
use App\Models\MedicationSchedule;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

/**
 * Build an unsaved medication with the given schedules, without touching the database.
 *
 * @param  array<string, mixed>  $attributes
 * @param  array<int, array<string, mixed>>  $schedules
 */
function medicationWithSchedules(array $attributes, array $schedules): Medication
{
    $medication = Medication::factory()->make($attributes);
    $medication->setRelation('schedules', new Collection(
        array_map(fn (array $schedule) => MedicationSchedule::factory()->make($schedule), $schedules),
    ));

    return $medication;
}

test('active scope excludes archived medications', function () {
    $user = User::factory()->create();
    $active = Medication::factory()->for($user)->create();
    Medication::factory()->for($user)->archived()->create();

    $medications = $user->medications()->active()->get();

    expect($medications->pluck('id')->all())->toBe([$active->id]);
});

test('deleting a user removes all their medications and schedules', function () {
    $user = User::factory()->create();
    $medication = Medication::factory()->for($user)->create();
    $schedule = MedicationSchedule::factory()->for($medication)->create();
    $archived = Medication::factory()->for($user)->create();
    $archived->delete();

    $user->delete();

    $this->assertModelMissing($medication);
    $this->assertModelMissing($schedule);
    $this->assertDatabaseMissing('medications', ['id' => $archived->id]);
});

test('deleting a medication keeps it in the database for history', function () {
    $medication = Medication::factory()->create();
    $schedule = MedicationSchedule::factory()->for($medication)->create();

    $medication->delete();

    $this->assertSoftDeleted($medication);
    $this->assertModelExists($schedule);
});

test('it reads back enums, decimals and days of the week', function () {
    $medication = Medication::factory()->create([
        'form' => MedicationForm::Capsule,
        'dose_amount' => 0.5,
    ]);
    $schedule = MedicationSchedule::factory()->for($medication)->onDays([1, 3, 5])->create();

    $medication->refresh();
    $schedule->refresh();

    expect($medication->form)->toBe(MedicationForm::Capsule);
    expect($medication->dose_amount)->toBe('0.50');
    expect($schedule->days_of_week)->toBe([1, 3, 5]);
});

test('it estimates how many days the stock lasts', function (array $attributes, array $schedules, ?int $expectedDays) {
    $medication = medicationWithSchedules($attributes, $schedules);

    expect($medication->daysOfStockLeft())->toBe($expectedDays);
})->with([
    'two daily doses' => [['stock_quantity' => 30, 'dose_amount' => 1], [['time' => '08:00'], ['time' => '20:00']], 15],
    'schedule overrides the dose' => [['stock_quantity' => 30, 'dose_amount' => 1], [['time' => '08:00'], ['time' => '20:00', 'dose_amount' => 2]], 10],
    'only three days a week' => [['stock_quantity' => 3, 'dose_amount' => 1], [['days_of_week' => [1, 3, 5]]], 7],
    'inactive schedules are ignored' => [['stock_quantity' => 30, 'dose_amount' => 1], [['time' => '08:00'], ['time' => '20:00', 'is_active' => false]], 30],
    'stock not tracked' => [['stock_quantity' => null], [['time' => '08:00']], null],
    'form measured in drops' => [['form' => MedicationForm::Drops, 'stock_quantity' => 30], [['time' => '08:00']], null],
    'no schedules' => [['stock_quantity' => 30], [], null],
]);

test('it is running low when the stock lasts up to the warning threshold', function (int $stock, bool $runningLow) {
    $medication = medicationWithSchedules(
        ['stock_quantity' => $stock, 'dose_amount' => 1, 'low_stock_days' => 5],
        [['time' => '08:00'], ['time' => '20:00']],
    );

    expect($medication->isRunningLow())->toBe($runningLow);
})->with([
    'five days left' => [10, true],
    'six days left' => [12, false],
]);

test('it formats amounts the brazilian way', function (string $amount, string $formatted) {
    expect(Medication::formatAmount($amount))->toBe($formatted);
})->with([
    ['1.00', '1'],
    ['0.50', '0,5'],
    ['2.25', '2,25'],
    ['100.00', '100'],
]);

test('it describes the days of the week of a schedule', function (?array $days, string $label) {
    $schedule = MedicationSchedule::factory()->make(['days_of_week' => $days]);

    expect($schedule->daysLabel())->toBe($label);
})->with([
    'every day' => [null, 'Todos os dias'],
    'all seven days' => [[1, 2, 3, 4, 5, 6, 7], 'Todos os dias'],
    'unordered days' => [[5, 1, 3], 'Seg, Qua, Sex'],
]);
