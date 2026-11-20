<?php

use App\Actions\GenerateDoses;
use App\Models\Dose;
use App\Models\Medication;
use App\Models\MedicationSchedule;
use App\Models\Reminder;
use App\Models\User;

/*
 * "Now" is Tuesday, 2026-12-01 10:00 UTC (07:00 in São Paulo).
 * The 48-hour window ends on Thursday, 2026-12-03 10:00 UTC.
 */
beforeEach(function () {
    $this->travelTo('2026-12-01 10:00:00');
});

/**
 * Create an active schedule for a user in the given time zone.
 *
 * @param  array<string, mixed>  $medication
 * @param  array<string, mixed>  $schedule
 */
function scheduleFor(string $timezone = 'America/Sao_Paulo', array $medication = [], array $schedule = []): MedicationSchedule
{
    $user = User::factory()->create(['timezone' => $timezone]);

    return MedicationSchedule::factory()
        ->for(Medication::factory()->for($user)->create(['starts_on' => '2026-01-01', ...$medication]))
        ->create($schedule);
}

/**
 * @return array<int, string>
 */
function doseTimes(): array
{
    return Dose::query()->orderBy('scheduled_for')->get()
        ->map(fn (Dose $dose) => $dose->scheduled_for->toDateTimeString())
        ->all();
}

test('it creates the doses of the next 48 hours in UTC', function () {
    scheduleFor(schedule: ['time' => '08:00:00']);

    $created = app(GenerateDoses::class)->forAllSchedules();

    expect($created)->toBe(2);
    expect(doseTimes())->toBe(['2026-12-01 11:00:00', '2026-12-02 11:00:00']);
});

test('times that already passed today are skipped', function () {
    scheduleFor(schedule: ['time' => '06:00:00']);

    app(GenerateDoses::class)->forAllSchedules();

    expect(doseTimes())->toBe(['2026-12-02 09:00:00', '2026-12-03 09:00:00']);
});

test('it respects the user time zone', function () {
    scheduleFor('America/Manaus', schedule: ['time' => '08:00:00']);

    app(GenerateDoses::class)->forAllSchedules();

    expect(doseTimes())->toBe(['2026-12-01 12:00:00', '2026-12-02 12:00:00']);
});

test('a late evening time falls on the next day in UTC', function () {
    scheduleFor(schedule: ['time' => '22:00:00']);

    app(GenerateDoses::class)->forAllSchedules();

    expect(doseTimes())->toBe(['2026-12-02 01:00:00', '2026-12-03 01:00:00']);
});

test('it only creates doses on the chosen days of the week', function () {
    scheduleFor(schedule: ['time' => '08:00:00', 'days_of_week' => [3]]);

    app(GenerateDoses::class)->forAllSchedules();

    expect(doseTimes())->toBe(['2026-12-02 11:00:00']);
});

test('it respects the start and end of the treatment', function (string $startsOn, ?string $endsOn, array $expected) {
    scheduleFor(medication: ['starts_on' => $startsOn, 'ends_on' => $endsOn], schedule: ['time' => '08:00:00']);

    app(GenerateDoses::class)->forAllSchedules();

    expect(doseTimes())->toBe($expected);
})->with([
    'starts tomorrow' => ['2026-12-02', null, ['2026-12-02 11:00:00']],
    'ends today' => ['2026-01-01', '2026-12-01', ['2026-12-01 11:00:00']],
    'already ended' => ['2026-01-01', '2026-11-30', []],
]);

test('it ignores archived medications, deleted medications and removed times', function () {
    scheduleFor(medication: ['is_active' => false]);
    scheduleFor(schedule: ['is_active' => false]);
    scheduleFor()->medication->delete();

    $created = app(GenerateDoses::class)->forAllSchedules();

    expect($created)->toBe(0);
    expect(Reminder::count())->toBe(0);
});

test('running it twice does not duplicate anything', function () {
    scheduleFor(schedule: ['time' => '08:00:00']);
    $generateDoses = app(GenerateDoses::class);

    $generateDoses->forAllSchedules();
    $createdOnSecondRun = $generateDoses->forAllSchedules();

    expect($createdOnSecondRun)->toBe(0);
    expect(Dose::count())->toBe(2);
    expect(Reminder::count())->toBe(2);
});

test('medications taken at the same time share one reminder', function () {
    $schedule = scheduleFor(schedule: ['time' => '08:00:00']);
    $otherMedication = Medication::factory()->for($schedule->medication->user)->create(['starts_on' => '2026-01-01']);
    MedicationSchedule::factory()->for($otherMedication)->create(['time' => '08:00:00']);

    app(GenerateDoses::class)->forAllSchedules();

    expect(Dose::count())->toBe(4);
    expect(Reminder::count())->toBe(2);
    expect(Reminder::first()->doses)->toHaveCount(2);
});

test('the dose amount comes from the schedule when it overrides the medication', function () {
    scheduleFor(medication: ['dose_amount' => 1], schedule: ['dose_amount' => 2]);

    app(GenerateDoses::class)->forAllSchedules();

    expect(Dose::pluck('amount')->unique()->values()->all())->toBe(['2.00']);
});

test('the command generates the doses and reports how many were created', function () {
    scheduleFor(schedule: ['time' => '08:00:00']);

    $this->artisan('doses:generate')
        ->expectsOutput('2 doses created.')
        ->assertSuccessful();

    expect(Dose::count())->toBe(2);
});
