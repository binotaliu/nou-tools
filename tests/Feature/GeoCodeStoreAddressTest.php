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
            && str_contains($request->url(), 'q=');
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

it('normalizes complex address with multiple alleys and floors', function () {
    Http::fake([
        '*' => Http::response([
            [
                'lat' => '25.0330',
                'lon' => '121.5654',
            ],
        ]),
    ]);

    $category = DiscountStoreCategory::factory()->create();

    // Address contains full city/district and floors - should extract: 中山路1巷2弄 3
    $store = DiscountStore::factory()->create([
        'category_id' => $category->id,
        'city' => '台北市',
        'district' => '中正區',
        'address' => '台北市中正區中山路1巷2弄3號4樓之5',
        'latitude' => null,
        'longitude' => null,
    ]);

    $action = app(GeoCodeStoreAddress::class);
    $action($store);

    Http::assertSent(function ($request) {
        return str_contains($request->url(), 'q=') &&
               str_contains($request->url(), rawurlencode('中山路1巷2弄'));
    });
});

it('normalizes address with alleys and floors when city/district not in address', function () {
    Http::fake([
        '*' => Http::response([
            [
                'lat' => '25.0330',
                'lon' => '121.5654',
            ],
        ]),
    ]);

    $category = DiscountStoreCategory::factory()->create();

    // Address without city/district prefix - should also extract: 中山路1巷2弄 3
    $store = DiscountStore::factory()->create([
        'category_id' => $category->id,
        'city' => '台北市',
        'district' => '中正區',
        'address' => '中山路1巷2弄3號4樓之5',
        'latitude' => null,
        'longitude' => null,
    ]);

    $action = app(GeoCodeStoreAddress::class);
    $action($store);

    Http::assertSent(function ($request) {
        return str_contains($request->url(), 'q=') &&
               str_contains($request->url(), rawurlencode('中山路1巷2弄'));
    });
});
