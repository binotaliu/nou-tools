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

test('admin user menu links back to the public site', function () {
    /** @var User&Authenticatable $user */
    $user = User::factory()->createOne();

    actingAs($user);

    get(route('filament.admin.auth.profile'))
        ->assertSee('回到 NOU 小幫手')
        ->assertSee(route('home'), false);
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
