<?php

use App\Models\Dose;
use App\Models\Medication;
use App\Models\MedicationSchedule;
use App\Models\User;

test('guests are redirected to the login page', function () {
    $response = $this->get(route('dashboard'));
    $response->assertRedirect(route('login'));
});

test('authenticated users can visit the dashboard', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $response = $this->get(route('dashboard'));
    $response->assertOk();
});

test('it shows the doses of the user\'s local day, grouped by time', function () {
    $this->travelTo('2026-12-01 02:30:00');
    $user = User::factory()->create(['timezone' => 'America/Sao_Paulo']);
    $medication = Medication::factory()->for($user)->create(['name' => 'Lavitan A-Z', 'dose_unit' => 'comprimido']);
    $schedule = MedicationSchedule::factory()->for($medication)->create();
    Dose::factory()->for($schedule, 'schedule')->create(['scheduled_for' => '2026-11-30 11:00:00', 'amount' => 1]);
    Dose::factory()->for($schedule, 'schedule')->create(['scheduled_for' => '2026-12-01 01:00:00', 'amount' => 2]);
    Dose::factory()->for($schedule, 'schedule')->create(['scheduled_for' => '2026-12-01 11:00:00', 'amount' => 3]);
    Dose::factory()->create(['scheduled_for' => '2026-11-30 12:00:00']);
    $this->actingAs($user);

    $response = $this->get(route('dashboard'));

    $response->assertOk()
        ->assertSee('segunda-feira, 30 de novembro')
        ->assertSeeInOrder(['08:00', 'Lavitan A-Z', '1 comprimido', '22:00', 'Lavitan A-Z', '2 comprimido'])
        ->assertDontSee('3 comprimido')
        ->assertSee('0 de 2 doses tomadas hoje');
});

test('it counts the doses taken today', function () {
    $this->travelTo('2026-12-01 15:00:00');
    $schedule = MedicationSchedule::factory()->create();
    Dose::factory()->for($schedule, 'schedule')->taken()->create(['scheduled_for' => '2026-12-01 11:00:00']);
    Dose::factory()->for($schedule, 'schedule')->create(['scheduled_for' => '2026-12-01 23:00:00']);
    $this->actingAs($schedule->medication->user);

    $response = $this->get(route('dashboard'));

    $response->assertOk()
        ->assertSee('1 de 2 doses tomadas hoje')
        ->assertSeeInOrder(['Tomado', 'Desfazer', 'Tomei', 'Pular']);
});

test('it celebrates when every dose of the day was taken', function () {
    $this->travelTo('2026-12-01 15:00:00');
    $schedule = MedicationSchedule::factory()->create();
    Dose::factory()->for($schedule, 'schedule')->taken()->create(['scheduled_for' => '2026-12-01 11:00:00']);
    $this->actingAs($schedule->medication->user);

    $response = $this->get(route('dashboard'));

    $response->assertOk()->assertSee('Você tomou tudo hoje 🎉');
});

test('it invites the user to add a medication when there is none', function () {
    $this->actingAs(User::factory()->create());

    $response = $this->get(route('dashboard'));

    $response->assertOk()
        ->assertSee('Nenhum remédio ainda')
        ->assertSee(route('medications.create'));
});

test('it explains when no medication has a dose today', function () {
    Medication::factory()->for($user = User::factory()->create())->create();
    $this->actingAs($user);

    $response = $this->get(route('dashboard'));

    $response->assertOk()->assertSee('Nenhuma dose hoje');
});
