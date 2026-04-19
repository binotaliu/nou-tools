<?php

use App\Models\User;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Validation\Rules\Password;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

test('authenticated user can access filament profile page', function () {
    /** @var User&Authenticatable $user */
    $user = User::factory()->createOne();

    actingAs($user);

    get(route('filament.admin.auth.profile'))
        ->assertSuccessful();
});

test('password default rule requires minimum 8 and uncompromised', function () {
    $passwordRule = Password::default();

    $reflection = new ReflectionClass($passwordRule);

    $minimumLengthProperty = $reflection->getProperty('min');
    $minimumLengthProperty->setAccessible(true);

    $uncompromisedProperty = $reflection->getProperty('uncompromised');
    $uncompromisedProperty->setAccessible(true);

    expect($minimumLengthProperty->getValue($passwordRule))->toBe(8);
    expect($uncompromisedProperty->getValue($passwordRule))->toBeTrue();
});
