<?php

use App\Enums\DiscountStoreStatus;
use App\Enums\DiscountStoreType;
use App\Models\DiscountStore;
use App\Models\DiscountStoreCategory;

it('renders the leaflet tile attribution as a real OpenStreetMap link, not an escaped string', function () {
    $store = DiscountStore::factory()
        ->for(DiscountStoreCategory::factory()->create(), 'category')
        ->create([
            'status' => DiscountStoreStatus::Online,
            'type' => DiscountStoreType::Local,
            'address' => '中正路 1 號',
            'latitude' => 22.9909,
            'longitude' => 120.1971,
        ]);

    $page = visit(route('discount-stores.show', $store));

    $page->assertNoJavaScriptErrors()
        ->assertVisible('.leaflet-control-attribution');

    $attributionHtml = $page->script(
        "document.querySelector('.leaflet-control-attribution')?.innerHTML"
    );

    expect($attributionHtml)->toBeString();
    expect($attributionHtml)->toContain('<a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>');
    expect($attributionHtml)->not->toContain('u0026');
    expect($attributionHtml)->not->toContain('u003C');
});
