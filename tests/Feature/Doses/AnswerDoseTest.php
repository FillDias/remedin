<?php

use App\Actions\MarkDoseAsTaken;
use App\Enums\DoseStatus;
use App\Enums\ReminderStatus;
use App\Models\Dose;
use App\Models\Medication;
use App\Models\MedicationSchedule;
use App\Models\User;
use Livewire\Livewire;

/*
 * "Now" is 2026-12-01 15:00 UTC (12:00 in São Paulo).
 */
beforeEach(function () {
    $this->travelTo('2026-12-01 15:00:00');

    $this->medication = Medication::factory()->create(['stock_quantity' => 10]);
    $this->schedule = MedicationSchedule::factory()->for($this->medication)->create();
    $this->dose = Dose::factory()->for($this->schedule, 'schedule')->create([
        'scheduled_for' => '2026-12-01 11:00:00',
        'amount' => 2,
    ]);

    $this->actingAs($this->medication->user);
});

test('taking a dose records the time and discounts the stock', function () {
    Livewire::test('pages::dashboard')
        ->call('take', $this->dose->id)
        ->assertHasNoErrors()
        ->assertSee('Você tomou tudo hoje 🎉');

    $this->dose->refresh();
    expect($this->dose->status)->toBe(DoseStatus::Taken);
    expect($this->dose->taken_at->toDateTimeString())->toBe('2026-12-01 15:00:00');
    expect($this->medication->refresh()->stock_quantity)->toBe('8.00');
    expect($this->dose->reminder->status)->toBe(ReminderStatus::Answered);
});

test('taking the same dose twice discounts the stock only once', function () {
    $markDoseAsTaken = app(MarkDoseAsTaken::class);

    $markDoseAsTaken->handle($this->dose);
    $markDoseAsTaken->handle($this->dose);

    expect($this->medication->refresh()->stock_quantity)->toBe('8.00');
});

test('the stock never goes below zero', function () {
    $this->medication->update(['stock_quantity' => 1]);

    app(MarkDoseAsTaken::class)->handle($this->dose);

    expect($this->medication->refresh()->stock_quantity)->toBe('0.00');
});

test('taking a dose without stock control keeps the stock empty', function () {
    $this->medication->update(['stock_quantity' => null]);

    app(MarkDoseAsTaken::class)->handle($this->dose);

    expect($this->medication->refresh()->stock_quantity)->toBeNull();
    expect($this->dose->refresh()->status)->toBe(DoseStatus::Taken);
});

test('the reminder stays open while another dose of it is pending', function () {
    $otherSchedule = MedicationSchedule::factory()->for(Medication::factory()->for($this->medication->user))->create();
    Dose::factory()->for($otherSchedule, 'schedule')->create(['scheduled_for' => '2026-12-01 11:00:00']);

    Livewire::test('pages::dashboard')->call('take', $this->dose->id);

    expect($this->dose->reminder->refresh()->status)->toBe(ReminderStatus::Pending);
});

test('skipping a dose keeps the stock', function () {
    Livewire::test('pages::dashboard')
        ->call('skip', $this->dose->id)
        ->assertHasNoErrors();

    expect($this->dose->refresh()->status)->toBe(DoseStatus::Skipped);
    expect($this->medication->refresh()->stock_quantity)->toBe('10.00');
});

test('a missed dose can still be marked as taken', function () {
    $this->dose->update(['status' => DoseStatus::Missed]);

    Livewire::test('pages::dashboard')->call('take', $this->dose->id);

    expect($this->dose->refresh()->status)->toBe(DoseStatus::Taken);
});

test('undoing a taken dose gives the units back to the stock', function () {
    app(MarkDoseAsTaken::class)->handle($this->dose);

    Livewire::test('pages::dashboard')
        ->call('undo', $this->dose->id)
        ->assertHasNoErrors();

    $this->dose->refresh();
    expect($this->dose->status)->toBe(DoseStatus::Pending);
    expect($this->dose->taken_at)->toBeNull();
    expect($this->medication->refresh()->stock_quantity)->toBe('10.00');
});

test('undoing reopens a future reminder but not a past one', function () {
    $futureDose = Dose::factory()->for($this->schedule, 'schedule')->create(['scheduled_for' => '2026-12-01 23:00:00']);
    app(MarkDoseAsTaken::class)->handle($this->dose);
    app(MarkDoseAsTaken::class)->handle($futureDose);

    Livewire::test('pages::dashboard')
        ->call('undo', $this->dose->id)
        ->call('undo', $futureDose->id);

    expect($this->dose->reminder->refresh()->status)->toBe(ReminderStatus::Answered);
    expect($futureDose->reminder->refresh()->status)->toBe(ReminderStatus::Pending);
});

test('users cannot answer a dose of another user', function () {
    $this->actingAs(User::factory()->create());

    Livewire::test('pages::dashboard')
        ->call('take', $this->dose->id)
        ->assertNotFound();

    expect($this->dose->refresh()->status)->toBe(DoseStatus::Pending);
});
