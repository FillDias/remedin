<?php

use App\Models\User;
use Laravel\Fortify\Features;

beforeEach(function () {
    $this->skipUnlessFortifyHas(Features::registration());
});

/**
 * @param  array<string, string>  $overrides
 * @return array<string, string>
 */
function registrationData(array $overrides = []): array
{
    return [
        'name' => 'John Doe',
        'email' => 'test@example.com',
        'phone' => '(11) 98765-4321',
        'password' => 'password',
        'password_confirmation' => 'password',
        'privacy_consent' => '1',
        ...$overrides,
    ];
}

test('registration screen can be rendered', function () {
    $response = $this->get(route('register'));

    $response->assertOk()
        ->assertSee('name="phone"', escape: false)
        ->assertSee('Concordo que o Remedin guarde informações sobre os meus remédios');
});

test('new users can register', function () {
    $this->freezeTime();

    $response = $this->post(route('register.store'), registrationData());

    $response->assertSessionHasNoErrors()
        ->assertRedirect(route('dashboard', absolute: false));
    $this->assertAuthenticated();
    $this->assertDatabaseHas('users', [
        'email' => 'test@example.com',
        'phone' => '+5511987654321',
        'timezone' => 'America/Sao_Paulo',
        'phone_verified_at' => null,
        'privacy_consent_at' => now(),
    ]);
});

test('registration requires a valid brazilian mobile number', function (string $phone) {
    $response = $this->post(route('register.store'), registrationData(['phone' => $phone]));

    $response->assertSessionHasErrors(['phone' => 'Informe um celular com DDD, ex.: (11) 98765-4321.']);
    $this->assertGuest();
    $this->assertDatabaseCount('users', 0);
})->with([
    'too short' => '98765-4321',
    'landline' => '(11) 3456-7890',
]);

test('registration rejects a phone number already in use', function () {
    User::factory()->create(['phone' => '+5511987654321']);

    $response = $this->post(route('register.store'), registrationData());

    $response->assertSessionHasErrors(['phone' => 'O valor indicado para o campo telefone já se encontra registrado.']);
    $this->assertGuest();
    $this->assertDatabaseCount('users', 1);
});

test('registration requires privacy consent', function () {
    $data = registrationData();
    unset($data['privacy_consent']);

    $response = $this->post(route('register.store'), $data);

    $response->assertSessionHasErrors(['privacy_consent' => 'Para criar a conta, você precisa aceitar os termos de privacidade.']);
    $this->assertGuest();
    $this->assertDatabaseCount('users', 0);
});
