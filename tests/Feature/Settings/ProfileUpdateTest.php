<?php

use App\Models\User;
use Livewire\Livewire;

test('profile page is displayed', function () {
    $this->actingAs($user = User::factory()->create());

    $this->get(route('profile.edit'))->assertOk();
});

test('profile information can be updated', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    $response = Livewire::test('pages::settings.profile')
        ->set('name', 'Test User')
        ->set('email', 'test@example.com')
        ->call('updateProfileInformation');

    $response->assertHasNoErrors();

    $user->refresh();

    expect($user->name)->toEqual('Test User');
    expect($user->email)->toEqual('test@example.com');
    expect($user->email_verified_at)->toBeNull();
});

test('email is stored in lowercase so the user can still log in', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    Livewire::test('pages::settings.profile')
        ->set('name', 'Test User')
        ->set('email', 'Test@Example.com')
        ->call('updateProfileInformation')
        ->assertHasNoErrors();

    expect($user->refresh()->email)->toEqual('test@example.com');

    $this->post(route('logout'));

    $this->post(route('login.store'), [
        'email' => 'Test@Example.com',
        'password' => 'password',
    ]);

    $this->assertAuthenticatedAs($user);
});

test('email must be unique regardless of case', function () {
    User::factory()->create(['email' => 'taken@example.com']);

    $this->actingAs(User::factory()->create());

    Livewire::test('pages::settings.profile')
        ->set('name', 'Test User')
        ->set('email', 'Taken@Example.com')
        ->call('updateProfileInformation')
        ->assertHasErrors(['email']);
});

test('email verification status is unchanged when email address is unchanged', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    $response = Livewire::test('pages::settings.profile')
        ->set('name', 'Test User')
        ->set('email', $user->email)
        ->call('updateProfileInformation');

    $response->assertHasNoErrors();

    expect($user->refresh()->email_verified_at)->not->toBeNull();
});

test('profile page shows the phone in national format', function () {
    $this->actingAs(User::factory()->create(['phone' => '+5511987654321']));

    Livewire::test('pages::settings.profile')
        ->assertSet('phone', '(11) 98765-4321');
});

test('changing the phone number resets its verification', function () {
    $user = User::factory()->create(['phone' => '+5511987654321']);

    $this->actingAs($user);

    Livewire::test('pages::settings.profile')
        ->set('phone', '(21) 99876-5432')
        ->call('updateProfileInformation')
        ->assertHasNoErrors()
        ->assertSet('phone', '(21) 99876-5432');

    $user->refresh();
    expect($user->phone)->toBe('+5521998765432');
    expect($user->phone_verified_at)->toBeNull();
    expect($user->whatsapp_opt_in_at)->toBeNull();
});

test('phone verification is unchanged when the phone number is unchanged', function () {
    $user = User::factory()->create(['phone' => '+5511987654321']);

    $this->actingAs($user);

    Livewire::test('pages::settings.profile')
        ->set('name', 'Test User')
        ->call('updateProfileInformation')
        ->assertHasNoErrors();

    $user->refresh();
    expect($user->phone_verified_at)->not->toBeNull();
    expect($user->whatsapp_opt_in_at)->not->toBeNull();
});

test('phone must be a valid brazilian mobile number', function () {
    $user = User::factory()->create(['phone' => '+5511987654321']);

    $this->actingAs($user);

    Livewire::test('pages::settings.profile')
        ->set('phone', '(11) 3456-7890')
        ->call('updateProfileInformation')
        ->assertHasErrors(['phone' => 'regex']);

    expect($user->refresh()->phone)->toBe('+5511987654321');
});

test('phone must not belong to another user', function () {
    User::factory()->create(['phone' => '+5521998765432']);
    $user = User::factory()->create(['phone' => '+5511987654321']);

    $this->actingAs($user);

    Livewire::test('pages::settings.profile')
        ->set('phone', '(21) 99876-5432')
        ->call('updateProfileInformation')
        ->assertHasErrors(['phone' => 'O valor indicado para o campo telefone já se encontra registrado.']);

    expect($user->refresh()->phone)->toBe('+5511987654321');
});

test('time zone can be updated', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    Livewire::test('pages::settings.profile')
        ->set('timezone', 'America/Manaus')
        ->call('updateProfileInformation')
        ->assertHasNoErrors();

    expect($user->refresh()->timezone)->toBe('America/Manaus');
});

test('time zone must be one of the supported brazilian time zones', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    Livewire::test('pages::settings.profile')
        ->set('timezone', 'Europe/Lisbon')
        ->call('updateProfileInformation')
        ->assertHasErrors(['timezone']);

    expect($user->refresh()->timezone)->toBe('America/Sao_Paulo');
});

test('user can delete their account', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    $response = Livewire::test('pages::settings.delete-user-modal')
        ->set('password', 'password')
        ->call('deleteUser');

    $response
        ->assertHasNoErrors()
        ->assertRedirect('/');

    expect($user->fresh())->toBeNull();
    expect(auth()->check())->toBeFalse();
});

test('correct password must be provided to delete account', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    $response = Livewire::test('pages::settings.delete-user-modal')
        ->set('password', 'wrong-password')
        ->call('deleteUser');

    $response->assertHasErrors(['password']);

    expect($user->fresh())->not->toBeNull();
});
