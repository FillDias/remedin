<?php

use App\Models\Medication;
use App\Models\MedicationSchedule;
use App\Models\User;
use Livewire\Livewire;

test('a user can archive their medication and keep its schedules', function () {
    $medication = Medication::factory()->create();
    $schedule = MedicationSchedule::factory()->for($medication)->create();
    $this->actingAs($medication->user);

    Livewire::test('pages::medications.form', ['medication' => $medication])
        ->call('archive')
        ->assertHasNoErrors()
        ->assertRedirect(route('medications.index'));

    expect($medication->refresh()->is_active)->toBeFalse();
    $this->assertModelExists($medication);
    expect($schedule->refresh()->is_active)->toBeTrue();
});

test('a user can reactivate an archived medication', function () {
    $medication = Medication::factory()->archived()->create();
    $this->actingAs($medication->user);

    Livewire::test('pages::medications.form', ['medication' => $medication])
        ->assertSee('Este remédio está arquivado')
        ->call('unarchive')
        ->assertHasNoErrors()
        ->assertDontSee('Este remédio está arquivado');

    expect($medication->refresh()->is_active)->toBeTrue();
});

test('archived medications are listed separately', function () {
    $user = User::factory()->create();
    Medication::factory()->for($user)->create(['name' => 'Vitamina D3']);
    Medication::factory()->for($user)->archived()->create(['name' => 'Antibiótico']);
    $this->actingAs($user);

    Livewire::test('pages::medications.index')
        ->assertSee('Vitamina D3')
        ->assertDontSee('Antibiótico')
        ->assertSee('Ver arquivados (1)')
        ->set('showArchived', true)
        ->assertSee('Antibiótico')
        ->assertDontSee('Vitamina D3');
});

test('the archived list can be opened from the url', function () {
    $user = User::factory()->create();
    Medication::factory()->for($user)->archived()->create(['name' => 'Antibiótico']);
    $this->actingAs($user);

    $response = $this->get(route('medications.index', ['arquivados' => 1]));

    $response->assertOk()->assertSee('Antibiótico');
});

test('archived medications of other users are not listed', function () {
    $user = User::factory()->create();
    Medication::factory()->archived()->create(['name' => 'Remédio De Outra Pessoa']);
    $this->actingAs($user);

    Livewire::test('pages::medications.index')
        ->set('showArchived', true)
        ->assertDontSee('Remédio De Outra Pessoa')
        ->assertSee('Nenhum remédio arquivado.');
});
