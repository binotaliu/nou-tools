<?php

use App\Models\DiscountStore;
use App\Models\DiscountStoreCategory;
use Illuminate\Support\Facades\Http;
use NouTools\Domains\DiscountStores\Actions\GeoCodeStoreAddress;

it('can geocode a store address', function () {
    Http::fake([
        '*' => Http::response([
            [
                'lat' => '25.0330',
                'lon' => '121.5654',
            ],
        ]),
    ]);

    $category = DiscountStoreCategory::factory()->create();

    $store = DiscountStore::factory()->create([
        'category_id' => $category->id,
        'city' => '台北市',
        'district' => '中正區',
        'address' => '中山路1號',
        'latitude' => null,
        'longitude' => null,
    ]);

    $action = app(GeoCodeStoreAddress::class);
    $coordinates = $action($store);

    expect($coordinates)
        ->toHaveKeys(['latitude', 'longitude'])
        ->latitude->toBe(25.0330)
        ->longitude->toBe(121.5654);

    Http::assertSent(function ($request) {
        return str_contains($request->url(), 'https://nominatim.openstreetmap.org/search')
            && str_contains($request->url(), 'q=')
            && str_contains($request->url(), 'format=jsonv2');
    });
});

it('returns null coordinates for empty address', function () {
    Http::fake();

    $category = DiscountStoreCategory::factory()->create();

    $store = DiscountStore::factory()->create([
        'category_id' => $category->id,
        'address' => '',
        'latitude' => null,
        'longitude' => null,
    ]);

    $action = app(GeoCodeStoreAddress::class);
    $coordinates = $action($store);

    expect($coordinates['latitude'])->toBeNull();
    expect($coordinates['longitude'])->toBeNull();
});
