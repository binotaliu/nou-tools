<?php

use App\Enums\UserRole;
use App\Filament\Resources\DiscountStores\Pages\EditDiscountStore;
use App\Models\DiscountStore;
use App\Models\DiscountStoreCategory;
use App\Models\User;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;

it('persists expires_at when saving the discount store edit form', function () {
    /** @var User $admin */
    $admin = User::factory()->create([
        'roles' => [UserRole::Admin->value],
    ]);

    actingAs($admin);

    $category = DiscountStoreCategory::factory()->create();

    $store = DiscountStore::factory()->create([
        'category_id' => $category->id,
        'expires_at' => null,
    ]);

    Livewire::test(EditDiscountStore::class, ['record' => $store->getRouteKey()])
        ->set('data.expires_at', '2026-12-31 18:30:00')
        ->call('save')
        ->assertHasNoErrors();

    $store->refresh();

    expect($store->expires_at)->not->toBeNull()
        ->and($store->expires_at->timezone('Asia/Taipei')->format('Y-m-d H:i:s'))->toBe('2026-12-31 18:30:00');
});

it('allows clearing expires_at on the discount store edit form', function () {
    /** @var User $admin */
    $admin = User::factory()->create([
        'roles' => [UserRole::Admin->value],
    ]);

    actingAs($admin);

    $category = DiscountStoreCategory::factory()->create();

    $store = DiscountStore::factory()->create([
        'category_id' => $category->id,
        'expires_at' => now()->addDay(),
    ]);

    Livewire::test(EditDiscountStore::class, ['record' => $store->getRouteKey()])
        ->set('data.expires_at', null)
        ->call('save')
        ->assertHasNoErrors();

    $store->refresh();

    expect($store->expires_at)->toBeNull();
});
