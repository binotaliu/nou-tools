<?php

use App\Enums\DiscountStoreStatus;
use App\Enums\DiscountStoreType;
use App\Enums\UserRole;
use App\Filament\Resources\DiscountStores\Pages\EditDiscountStore;
use App\Models\DiscountStore;
use App\Models\DiscountStoreCategory;
use App\Models\User;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;

it('shows the publish action and hides the unpublish action when the store is pending', function () {
    /** @var User $admin */
    $admin = User::factory()->create([
        'roles' => [UserRole::Admin->value],
    ]);

    actingAs($admin);

    $category = DiscountStoreCategory::factory()->create();

    $store = DiscountStore::factory()->create([
        'category_id' => $category->id,
        'type' => DiscountStoreType::Local,
        'status' => DiscountStoreStatus::Pending,
    ]);

    Livewire::test(EditDiscountStore::class, ['record' => $store->getRouteKey()])
        ->assertActionVisible('publish')
        ->assertActionHidden('unpublish');
});

it('publishes a pending store', function () {
    /** @var User $admin */
    $admin = User::factory()->create([
        'roles' => [UserRole::Admin->value],
    ]);

    actingAs($admin);

    $category = DiscountStoreCategory::factory()->create();

    $store = DiscountStore::factory()->create([
        'category_id' => $category->id,
        'type' => DiscountStoreType::Local,
        'status' => DiscountStoreStatus::Pending,
    ]);

    Livewire::test(EditDiscountStore::class, ['record' => $store->getRouteKey()])
        ->callAction('publish')
        ->assertNotified()
        ->assertSet('data.status', DiscountStoreStatus::Online->value);

    expect($store->refresh()->status)->toBe(DiscountStoreStatus::Online);
});

it('shows the unpublish action and hides the publish action when the store is online', function () {
    /** @var User $admin */
    $admin = User::factory()->create([
        'roles' => [UserRole::Admin->value],
    ]);

    actingAs($admin);

    $category = DiscountStoreCategory::factory()->create();

    $store = DiscountStore::factory()->create([
        'category_id' => $category->id,
        'type' => DiscountStoreType::Local,
        'status' => DiscountStoreStatus::Online,
    ]);

    Livewire::test(EditDiscountStore::class, ['record' => $store->getRouteKey()])
        ->assertActionVisible('unpublish')
        ->assertActionHidden('publish');
});

it('unpublishes an online store', function () {
    /** @var User $admin */
    $admin = User::factory()->create([
        'roles' => [UserRole::Admin->value],
    ]);

    actingAs($admin);

    $category = DiscountStoreCategory::factory()->create();

    $store = DiscountStore::factory()->create([
        'category_id' => $category->id,
        'type' => DiscountStoreType::Local,
        'status' => DiscountStoreStatus::Online,
    ]);

    Livewire::test(EditDiscountStore::class, ['record' => $store->getRouteKey()])
        ->callAction('unpublish')
        ->assertNotified()
        ->assertSet('data.status', DiscountStoreStatus::Expired->value);

    expect($store->refresh()->status)->toBe(DiscountStoreStatus::Expired);
});

it('hides both publish and unpublish actions when the store is expired', function () {
    /** @var User $admin */
    $admin = User::factory()->create([
        'roles' => [UserRole::Admin->value],
    ]);

    actingAs($admin);

    $category = DiscountStoreCategory::factory()->create();

    $store = DiscountStore::factory()->create([
        'category_id' => $category->id,
        'type' => DiscountStoreType::Local,
        'status' => DiscountStoreStatus::Expired,
    ]);

    Livewire::test(EditDiscountStore::class, ['record' => $store->getRouteKey()])
        ->assertActionHidden('publish')
        ->assertActionHidden('unpublish');
});
