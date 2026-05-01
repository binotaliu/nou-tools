<?php

use App\Enums\DiscountStoreType;
use App\Enums\UserRole;
use App\Filament\Resources\DiscountStores\Pages\EditDiscountStore;
use App\Models\DiscountStore;
use App\Models\DiscountStoreCategory;
use App\Models\User;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;

it('persists coordinates when saving the discount store edit form', function () {
    /** @var User $admin */
    $admin = User::factory()->create([
        'roles' => [UserRole::Admin->value],
    ]);

    actingAs($admin);

    $category = DiscountStoreCategory::factory()->create();

    $store = DiscountStore::factory()->create([
        'category_id' => $category->id,
        'type' => DiscountStoreType::Local,
        'latitude' => null,
        'longitude' => null,
    ]);

    Livewire::test(EditDiscountStore::class, ['record' => $store->getRouteKey()])
        ->set('data.latitude', 23.8649809)
        ->set('data.longitude', 120.9486389)
        ->call('save')
        ->assertHasNoErrors();

    $store->refresh();

    expect((float) $store->latitude)->toEqualWithDelta(23.8649809, 0.00000001)
        ->and((float) $store->longitude)->toEqualWithDelta(120.9486389, 0.00000001);
});

it('syncs location state to latitude and longitude when saving the discount store edit form', function () {
    /** @var User $admin */
    $admin = User::factory()->create([
        'roles' => [UserRole::Admin->value],
    ]);

    actingAs($admin);

    $category = DiscountStoreCategory::factory()->create();

    $store = DiscountStore::factory()->create([
        'category_id' => $category->id,
        'type' => DiscountStoreType::Local,
        'latitude' => null,
        'longitude' => null,
    ]);

    Livewire::test(EditDiscountStore::class, ['record' => $store->getRouteKey()])
        ->set('data.location', [
            'lat' => 25.033964,
            'lng' => 121.564468,
        ])
        ->call('save')
        ->assertHasNoErrors();

    $store->refresh();

    expect((float) $store->latitude)->toEqualWithDelta(25.033964, 0.00000001)
        ->and((float) $store->longitude)->toEqualWithDelta(121.564468, 0.00000001);
});
