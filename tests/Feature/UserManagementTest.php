<?php

use App\Enums\UserRole;
use App\Models\User;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertDatabaseHas;

test('user can be created with admin role', function () {
    $user = User::factory()->create([
        'name' => 'Admin User',
        'email' => 'admin@example.com',
        'roles' => [UserRole::Admin->value],
    ]);

    assertDatabaseHas('users', [
        'email' => 'admin@example.com',
        'roles' => json_encode([UserRole::Admin->value]),
    ]);

    expect($user->isAdmin())->toBeTrue();
    expect($user->isDiscountStoreManager())->toBeFalse();
});

test('user can be created with discount store role', function () {
    $user = User::factory()->create([
        'name' => 'Store Manager',
        'email' => 'store@example.com',
        'roles' => [UserRole::DiscountStore->value],
    ]);

    assertDatabaseHas('users', [
        'email' => 'store@example.com',
    ]);

    expect($user->isAdmin())->toBeFalse();
    expect($user->isDiscountStoreManager())->toBeTrue();
});

test('user can have multiple roles', function () {
    $user = User::factory()->create([
        'name' => 'Multi Role User',
        'email' => 'multi@example.com',
        'roles' => [UserRole::Admin->value, UserRole::DiscountStore->value],
    ]);

    expect($user->isAdmin())->toBeTrue();
    expect($user->isDiscountStoreManager())->toBeTrue();
});

test('user roles can be retrieved as enums', function () {
    $user = User::factory()->create([
        'roles' => [UserRole::Admin->value, UserRole::DiscountStore->value],
    ]);

    $roles = $user->getRoles();

    expect($roles)->toHaveCount(2);
    expect($roles[0])->toBe(UserRole::Admin);
    expect($roles[1])->toBe(UserRole::DiscountStore);
});

test('user role can be added', function () {
    $user = User::factory()->create([
        'roles' => [UserRole::Admin->value],
    ]);

    $user->addRole(UserRole::DiscountStore);

    expect($user->roles)->toEqual([UserRole::Admin->value, UserRole::DiscountStore->value]);
});

test('user role can be removed', function () {
    $user = User::factory()->create([
        'roles' => [UserRole::Admin->value, UserRole::DiscountStore->value],
    ]);

    $user->removeRole(UserRole::DiscountStore);

    expect($user->roles)->toEqual([UserRole::Admin->value]);
});

test('only admin can view user list in filament', function () {
    $admin = User::factory()->create(['roles' => [UserRole::Admin->value]]);
    $user = User::factory()->create(['roles' => [UserRole::DiscountStore->value]]);

    actingAs($admin);
    $this->assertTrue($admin->can('viewAny', User::class));

    actingAs($user);
    $this->assertFalse($user->can('viewAny', User::class));
});

test('only admin can create users in filament', function () {
    $admin = User::factory()->create(['roles' => [UserRole::Admin->value]]);
    $user = User::factory()->create(['roles' => [UserRole::DiscountStore->value]]);

    actingAs($admin);
    $this->assertTrue($admin->can('create', User::class));

    actingAs($user);
    $this->assertFalse($user->can('create', User::class));
});
