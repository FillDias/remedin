<?php

use App\Enums\MealRelation;
use App\Enums\MedicationForm;
use App\Models\Medication;
use App\Models\User;
use Livewire\Livewire;

test('guests cannot open the new medication page', function () {
    $response = $this->get(route('medications.create'));

    $response->assertRedirect(route('login'));
});

test('the new medication form starts with sensible defaults', function () {
    $this->travelTo('2026-03-10 02:00:00');
    $this->actingAs(User::factory()->create(['timezone' => 'America/Sao_Paulo']));

    $response = $this->get(route('medications.create'));

    $response->assertOk();
    Livewire::test('pages::medications.form')
        ->assertSet('dose_unit', 'comprimido')
        ->assertSet('starts_on', '2026-03-09');
});

test('a user can register a medication', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $component = Livewire::test('pages::medications.form')
        ->set('name', 'Lavitan A-Z')
        ->set('strength', '')
        ->set('form', 'tablet')
        ->set('dose_amount', '0,5')
        ->set('meal_relation', 'after')
        ->set('stock_quantity', '30')
        ->set('starts_on', '2026-01-01')
        ->call('save');

    $medication = $user->medications()->sole();
    $component->assertHasNoErrors()
        ->assertRedirect(route('medications.edit', $medication));
    expect($medication->name)->toBe('Lavitan A-Z');
    expect($medication->strength)->toBeNull();
    expect($medication->dose_amount)->toBe('0.50');
    expect($medication->dose_unit)->toBe('comprimido');
    expect($medication->meal_relation)->toBe(MealRelation::After);
    expect($medication->stock_quantity)->toBe('30.00');
    expect($medication->ends_on)->toBeNull();
});

test('required fields are validated', function () {
    $this->actingAs(User::factory()->create());

    Livewire::test('pages::medications.form')
        ->set('name', '')
        ->set('dose_amount', '')
        ->set('dose_unit', '')
        ->set('starts_on', '')
        ->call('save')
        ->assertHasErrors(['name' => 'required', 'dose_amount' => 'required', 'dose_unit' => 'required', 'starts_on' => 'required']);

    expect(Medication::count())->toBe(0);
});

test('the dose must be greater than zero', function () {
    $this->actingAs(User::factory()->create());

    Livewire::test('pages::medications.form')
        ->set('name', 'Lavitan A-Z')
        ->set('dose_amount', '0')
        ->call('save')
        ->assertHasErrors(['dose_amount' => 'min']);
});

test('the end date cannot be before the start date', function () {
    $this->actingAs(User::factory()->create());

    Livewire::test('pages::medications.form')
        ->set('name', 'Antibiótico')
        ->set('starts_on', '2026-05-10')
        ->set('ends_on', '2026-05-01')
        ->call('save')
        ->assertHasErrors(['ends_on' => 'after_or_equal']);
});

test('choosing a form suggests its dose unit unless the user typed a custom one', function () {
    $this->actingAs(User::factory()->create());

    Livewire::test('pages::medications.form')
        ->set('form', MedicationForm::Drops->value)
        ->assertSet('dose_unit', 'gotas')
        ->set('dose_unit', 'sachê')
        ->set('form', MedicationForm::Powder->value)
        ->assertSet('dose_unit', 'sachê');
});

test('stock is not saved for forms that are not counted in units', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    Livewire::test('pages::medications.form')
        ->set('name', 'Vitamina D gotas')
        ->set('form', MedicationForm::Drops->value)
        ->set('stock_quantity', '30')
        ->call('save')
        ->assertHasNoErrors();

    expect($user->medications()->sole()->stock_quantity)->toBeNull();
});

test('the edit form is filled with the medication data', function () {
    $medication = Medication::factory()->create(['name' => 'Losartana', 'dose_amount' => 0.5, 'stock_quantity' => null]);
    $this->actingAs($medication->user);

    Livewire::test('pages::medications.form', ['medication' => $medication])
        ->assertSet('name', 'Losartana')
        ->assertSet('dose_amount', '0,5')
        ->assertSet('stock_quantity', '');
});

test('a user can update their medication', function () {
    $medication = Medication::factory()->create(['name' => 'Losartana']);
    $this->actingAs($medication->user);

    Livewire::test('pages::medications.form', ['medication' => $medication])
        ->set('name', 'Losartana Potássica')
        ->call('save')
        ->assertHasNoErrors()
        ->assertRedirect(route('medications.index'));

    expect($medication->refresh()->name)->toBe('Losartana Potássica');
    expect(Medication::count())->toBe(1);
});

test('restocking clears the low stock warning so it can be sent again later', function () {
    $medication = Medication::factory()->create(['stock_quantity' => 2, 'low_stock_notified_at' => now()]);
    $this->actingAs($medication->user);

    Livewire::test('pages::medications.form', ['medication' => $medication])
        ->set('stock_quantity', '60')
        ->call('save')
        ->assertHasNoErrors();

    expect($medication->refresh()->low_stock_notified_at)->toBeNull();
});

test('users cannot open the edit page of another user\'s medication', function () {
    $medication = Medication::factory()->create();
    $this->actingAs(User::factory()->create());

    $response = $this->get(route('medications.edit', $medication));

    $response->assertNotFound();
});
