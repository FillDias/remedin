<?php

use App\Models\Medication;
use App\Models\MedicationSchedule;
use App\Models\User;

test('guests are redirected to the login page', function () {
    $response = $this->get(route('medications.index'));

    $response->assertRedirect(route('login'));
});

test('an empty state is shown when the user has no medications', function () {
    $this->actingAs(User::factory()->create());

    $response = $this->get(route('medications.index'));

    $response->assertOk()
        ->assertSee('Nenhum remédio ainda');
});

test('only the user\'s active medications are listed', function () {
    $user = User::factory()->create();
    Medication::factory()->for($user)->create(['name' => 'Vitamina D3']);
    Medication::factory()->for($user)->archived()->create(['name' => 'Remédio Arquivado']);
    Medication::factory()->create(['name' => 'Remédio De Outra Pessoa']);

    $this->actingAs($user);
    $response = $this->get(route('medications.index'));

    $response->assertOk()
        ->assertSee('Vitamina D3')
        ->assertDontSee('Remédio Arquivado')
        ->assertDontSee('Remédio De Outra Pessoa');
});

test('each medication shows its dose, times and stock', function () {
    $user = User::factory()->create();
    $medication = Medication::factory()->for($user)->create([
        'name' => 'Lavitan A-Z',
        'dose_amount' => 1,
        'dose_unit' => 'comprimido',
        'stock_quantity' => 8,
        'low_stock_days' => 5,
    ]);
    MedicationSchedule::factory()->for($medication)->create(['time' => '20:00']);
    MedicationSchedule::factory()->for($medication)->onDays([1, 3, 5])->create(['time' => '08:00']);

    $this->actingAs($user);
    $response = $this->get(route('medications.index'));

    $response->assertOk()
        ->assertSeeInOrder(['Lavitan A-Z', '1 comprimido', '08:00', 'Seg, Qua, Sex', '20:00', 'Todos os dias'])
        ->assertSee('Estoque: 8 · dura cerca de 5 dias')
        ->assertSee('Acabando');
});
