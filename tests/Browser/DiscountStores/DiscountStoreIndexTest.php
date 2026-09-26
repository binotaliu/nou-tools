<?php

use App\Enums\DiscountStoreStatus;
use App\Enums\DiscountStoreType;
use App\Models\DiscountStore;
use App\Models\DiscountStoreCategory;

it('filters the store list live from the chip selects and clears them', function () {
    $food = DiscountStoreCategory::factory()->create(['name' => '美食']);
    $books = DiscountStoreCategory::factory()->create(['name' => '書店']);

    DiscountStore::factory()->for($food, 'category')->create([
        'name' => '好吃便當',
        'status' => DiscountStoreStatus::Online,
        'type' => DiscountStoreType::Local,
    ]);
    DiscountStore::factory()->for($books, 'category')->create([
        'name' => '讀冊書店',
        'status' => DiscountStoreStatus::Online,
        'type' => DiscountStoreType::Local,
    ]);

    $page = visit('/discount-stores')->resize(390, 844);

    $page->assertNoJavaScriptErrors()
        ->assertSeeIn('[data-testid="discount-store-count"]', '共 2 家')
        ->select('#category', (string) $books->id)
        ->assertSeeIn('[data-testid="discount-store-count"]', '共 1 家')
        ->assertSee('讀冊書店')
        ->assertDontSee('好吃便當')
        ->click('清除')
        ->assertSeeIn('[data-testid="discount-store-count"]', '共 2 家')
        ->type('#search', '便當')
        ->assertSeeIn('[data-testid="discount-store-count"]', '共 1 家')
        ->assertSee('好吃便當');
});

it('keeps the phone layout within the viewport and makes each row a link to the store', function () {
    $store = DiscountStore::factory()
        ->for(DiscountStoreCategory::factory()->create(), 'category')
        ->create([
            'name' => '測試店家',
            'status' => DiscountStoreStatus::Online,
            'type' => DiscountStoreType::Local,
        ]);

    $page = visit('/discount-stores')->resize(390, 844);

    $page->assertNoJavaScriptErrors()
        ->assertPresent("#store-{$store->id} a[href$=\"/discount-stores/{$store->id}\"]");

    expect($page->script('document.documentElement.scrollWidth <= window.innerWidth'))->toBeTrue();
});
