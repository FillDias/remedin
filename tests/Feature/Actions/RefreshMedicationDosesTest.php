<?php

use App\Actions\GenerateDoses;
use App\Actions\RefreshMedicationDoses;
use App\Enums\DoseStatus;
use App\Models\Dose;
use App\Models\Medication;
use App\Models\MedicationSchedule;
use App\Models\Reminder;
use App\Models\User;
use Livewire\Livewire;

/*
 * "Now" is Tuesday, 2026-12-01 10:00 UTC (07:00 in São Paulo).
 */
beforeEach(function () {
    $this->travelTo('2026-12-01 10:00:00');

    $this->user = User::factory()->create(['timezone' => 'America/Sao_Paulo']);
    $this->medication = Medication::factory()->for($this->user)->create(['starts_on' => '2026-01-01', 'dose_amount' => 1]);
    $this->schedule = MedicationSchedule::factory()->for($this->medication)->create(['time' => '08:00:00']);
    app(GenerateDoses::class)->forAllSchedules();

    $this->actingAs($this->user);
});

test('past and answered doses are kept as history', function () {
    $pastDose = Dose::factory()->create(['medication_schedule_id' => $this->schedule->id, 'scheduled_for' => '2026-11-30 11:00:00']);
    $takenFutureDose = $this->medication->doses()->where('scheduled_for', '2026-12-01 11:00:00')->sole();
    $takenFutureDose->update(['status' => DoseStatus::Taken]);

    app(RefreshMedicationDoses::class)->handle($this->medication->fresh());

    $this->assertModelExists($pastDose);
    $this->assertModelExists($takenFutureDose);
    expect($this->medication->doses()->count())->toBe(3);
});

test('a reminder shared with another medication is kept when one medication is archived', function () {
    $otherMedication = Medication::factory()->for($this->user)->create(['starts_on' => '2026-01-01']);
    MedicationSchedule::factory()->for($otherMedication)->create(['time' => '08:00:00']);
    app(GenerateDoses::class)->forAllSchedules();

    Livewire::test('pages::medications.form', ['medication' => $this->medication])->call('archive');

    expect($this->medication->doses()->count())->toBe(0);
    expect($otherMedication->doses()->count())->toBe(2);
    expect(Reminder::count())->toBe(2);
});

test('adding a time creates its doses right away', function () {
    Livewire::test('medications.schedules', ['medication' => $this->medication])
        ->set('time', '20:00')
        ->call('add');

    expect($this->medication->doses()->where('scheduled_for', '2026-12-01 23:00:00')->exists())->toBeTrue();
    expect($this->medication->doses()->count())->toBe(4);
});

test('removing a time deletes its upcoming doses and empty reminders', function () {
    Livewire::test('medications.schedules', ['medication' => $this->medication])
        ->call('remove', $this->schedule->id);

    expect($this->medication->doses()->count())->toBe(0);
    expect(Reminder::count())->toBe(0);
});

test('archiving deletes the upcoming doses and reactivating brings them back', function () {
    $component = Livewire::test('pages::medications.form', ['medication' => $this->medication]);

    $component->call('archive');
    expect($this->medication->doses()->count())->toBe(0);

    Livewire::test('pages::medications.form', ['medication' => $this->medication->fresh()])->call('unarchive');
    expect($this->medication->doses()->count())->toBe(2);
});

test('changing the dose updates the upcoming doses', function () {
    Livewire::test('pages::medications.form', ['medication' => $this->medication])
        ->set('dose_amount', '2')
        ->call('save')
        ->assertHasNoErrors();

    expect($this->medication->doses()->pluck('amount')->unique()->values()->all())->toBe(['2.00']);
});

test('ending the treatment today removes the doses after today', function () {
    Livewire::test('pages::medications.form', ['medication' => $this->medication])
        ->set('ends_on', '2026-12-01')
        ->call('save')
        ->assertHasNoErrors();

    expect($this->medication->doses()->pluck('scheduled_for')->map->toDateTimeString()->all())
        ->toBe(['2026-12-01 11:00:00']);
});
