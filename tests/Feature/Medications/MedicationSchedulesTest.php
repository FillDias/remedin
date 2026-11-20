<?php

use App\Models\Medication;
use App\Models\MedicationSchedule;
use App\Models\User;
use Livewire\Livewire;

test('the edit page shows the reminder times', function () {
    $medication = Medication::factory()->create();
    MedicationSchedule::factory()->for($medication)->create(['time' => '21:30:00']);
    $this->actingAs($medication->user);

    $response = $this->get(route('medications.edit', $medication));

    $response->assertOk()
        ->assertSee('Horários dos lembretes')
        ->assertSee('21:30');
});

test('a reminder time can be added for every day', function () {
    $medication = Medication::factory()->create();
    $this->actingAs($medication->user);

    Livewire::test('medications.schedules', ['medication' => $medication])
        ->set('time', '08:00')
        ->call('add')
        ->assertHasNoErrors();

    $schedule = $medication->schedules()->sole();
    expect($schedule->timeLabel())->toBe('08:00');
    expect($schedule->days_of_week)->toBeNull();
    expect($schedule->dose_amount)->toBeNull();
    expect($schedule->is_active)->toBeTrue();
});

test('a reminder time can be added for specific days with a different dose', function () {
    $medication = Medication::factory()->create();
    $this->actingAs($medication->user);

    Livewire::test('medications.schedules', ['medication' => $medication])
        ->set('time', '20:00')
        ->set('frequency', 'specific_days')
        ->set('days', ['5', '1', '3'])
        ->set('dose_amount', '2')
        ->call('add')
        ->assertHasNoErrors();

    $schedule = $medication->schedules()->sole();
    expect($schedule->days_of_week)->toBe([1, 3, 5]);
    expect($schedule->dose_amount)->toBe('2.00');
});

test('choosing all seven days is stored as every day', function () {
    $medication = Medication::factory()->create();
    $this->actingAs($medication->user);

    Livewire::test('medications.schedules', ['medication' => $medication])
        ->set('frequency', 'specific_days')
        ->set('days', ['1', '2', '3', '4', '5', '6', '7'])
        ->call('add')
        ->assertHasNoErrors();

    expect($medication->schedules()->sole()->days_of_week)->toBeNull();
});

test('specific days require at least one day', function () {
    $medication = Medication::factory()->create();
    $this->actingAs($medication->user);

    Livewire::test('medications.schedules', ['medication' => $medication])
        ->set('frequency', 'specific_days')
        ->set('days', [])
        ->call('add')
        ->assertHasErrors(['days' => 'required']);

    expect($medication->schedules()->count())->toBe(0);
});

test('the time must be valid', function () {
    $medication = Medication::factory()->create();
    $this->actingAs($medication->user);

    Livewire::test('medications.schedules', ['medication' => $medication])
        ->set('time', '25:00')
        ->call('add')
        ->assertHasErrors(['time' => 'date_format']);
});

test('the same time cannot be added twice', function () {
    $medication = Medication::factory()->create();
    MedicationSchedule::factory()->for($medication)->create(['time' => '08:00:00']);
    $this->actingAs($medication->user);

    Livewire::test('medications.schedules', ['medication' => $medication])
        ->set('time', '08:00')
        ->call('add')
        ->assertHasErrors(['time']);

    expect($medication->schedules()->count())->toBe(1);
});

test('a removed time can be added again', function () {
    $medication = Medication::factory()->create();
    MedicationSchedule::factory()->for($medication)->create(['time' => '08:00:00', 'is_active' => false]);
    $this->actingAs($medication->user);

    Livewire::test('medications.schedules', ['medication' => $medication])
        ->set('time', '08:00')
        ->call('add')
        ->assertHasNoErrors();

    expect($medication->schedules()->where('is_active', true)->count())->toBe(1);
});

test('removing a time deactivates it and keeps it for history', function () {
    $medication = Medication::factory()->create();
    $schedule = MedicationSchedule::factory()->for($medication)->create();
    $this->actingAs($medication->user);

    Livewire::test('medications.schedules', ['medication' => $medication])
        ->call('remove', $schedule->id)
        ->assertHasNoErrors();

    $this->assertModelExists($schedule);
    expect($schedule->refresh()->is_active)->toBeFalse();
});

test('a schedule of another medication cannot be removed', function () {
    $medication = Medication::factory()->create();
    $otherSchedule = MedicationSchedule::factory()->create();
    $this->actingAs($medication->user);

    Livewire::test('medications.schedules', ['medication' => $medication])
        ->call('remove', $otherSchedule->id)
        ->assertNotFound();

    expect($otherSchedule->refresh()->is_active)->toBeTrue();
});

test('users cannot manage the schedules of another user\'s medication', function () {
    $medication = Medication::factory()->create();
    $this->actingAs(User::factory()->create());

    Livewire::test('medications.schedules', ['medication' => $medication])
        ->assertNotFound();
});
