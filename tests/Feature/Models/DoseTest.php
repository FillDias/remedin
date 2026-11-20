<?php

use App\Enums\DoseStatus;
use App\Models\Dose;
use App\Models\Medication;
use App\Models\MedicationSchedule;
use App\Models\User;

test('a dose belongs to a reminder of the medication owner', function () {
    $dose = Dose::factory()->create();

    expect($dose->reminder->user_id)->toBe($dose->medication->user_id);
    expect($dose->schedule->medication_id)->toBe($dose->medication_id);
    expect($dose->status)->toBe(DoseStatus::Pending);
});

test('doses at the same time for the same user share one reminder', function () {
    $user = User::factory()->create();
    $vitaminSchedule = MedicationSchedule::factory()->for(Medication::factory()->for($user))->create();
    $omegaSchedule = MedicationSchedule::factory()->for(Medication::factory()->for($user))->create();

    $first = Dose::factory()->create(['medication_schedule_id' => $vitaminSchedule->id, 'scheduled_for' => '2026-12-01 11:00:00']);
    $second = Dose::factory()->create(['medication_schedule_id' => $omegaSchedule->id, 'scheduled_for' => '2026-12-01 11:00:00']);

    expect($second->reminder_id)->toBe($first->reminder_id);
    expect($first->reminder->doses)->toHaveCount(2);
});

test('the history keeps showing a medication that was deleted', function () {
    $dose = Dose::factory()->create();

    $dose->medication->delete();

    expect($dose->refresh()->medication->name)->not->toBeEmpty();
});

test('deleting a user removes their reminders and doses', function () {
    $dose = Dose::factory()->create();
    $reminder = $dose->reminder;

    $dose->medication->user->delete();

    $this->assertModelMissing($reminder);
    $this->assertModelMissing($dose);
});
