<?php

use App\Models\Medication;
use App\Models\User;
use Illuminate\Support\Facades\Gate;

test('owner can view, update and delete their medication', function (string $ability) {
    $medication = Medication::factory()->create();

    $response = Gate::forUser($medication->user)->inspect($ability, $medication);

    expect($response->allowed())->toBeTrue();
})->with(['view', 'update', 'delete']);

test('other users get not found for a medication they do not own', function (string $ability) {
    $medication = Medication::factory()->create();
    $otherUser = User::factory()->create();

    $response = Gate::forUser($otherUser)->inspect($ability, $medication);

    expect($response->denied())->toBeTrue();
    expect($response->status())->toBe(404);
})->with(['view', 'update', 'delete']);

test('any user can list and register medications', function (string $ability) {
    $user = User::factory()->create();

    $allowed = Gate::forUser($user)->allows($ability, Medication::class);

    expect($allowed)->toBeTrue();
})->with(['viewAny', 'create']);
