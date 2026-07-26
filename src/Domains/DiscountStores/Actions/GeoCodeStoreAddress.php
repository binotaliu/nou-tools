<?php

declare(strict_types=1);

namespace NouTools\Domains\DiscountStores\Actions;

use App\Models\DiscountStore;
use Illuminate\Support\Facades\Http;

final readonly class GeoCodeStoreAddress
{
    public function __construct(
        private NormalizeStoreAddress $normalizeStoreAddress,
    ) {}

    /**
     * Geocode a store's address using OpenStreetMap Nominatim service.
     *
     * @return array{latitude: float|null, longitude: float|null}
     */
    public function __invoke(DiscountStore $store): array
    {
        if (! $store->address) {
            return ['latitude' => null, 'longitude' => null];
        }

        // Build query with city and district for better accuracy
        $query = $this->buildQuery($store);

        try {
            $response = Http::timeout(10)
                ->connectTimeout(5)
                ->acceptJson()
                ->withHeaders([
                    'User-Agent' => sprintf('%s geocoder', (string) config('app.name', 'nou-tools')),
                ])
                ->get('https://nominatim.openstreetmap.org/search', [
                    'format' => 'jsonv2',
                    'limit' => 1,
                    'q' => $query,
                ]);

            if (! $response->successful()) {
                return ['latitude' => null, 'longitude' => null];
            }

            $results = $response->json();

            if (empty($results) || ! isset($results[0]['lat'], $results[0]['lon'])) {
                return ['latitude' => null, 'longitude' => null];
            }

            $latitude = (float) $results[0]['lat'];
            $longitude = (float) $results[0]['lon'];

            if (is_nan($latitude) || is_nan($longitude)) {
                return ['latitude' => null, 'longitude' => null];
            }

            return ['latitude' => $latitude, 'longitude' => $longitude];
        } catch (\Exception) {
            return ['latitude' => null, 'longitude' => null];
        }
    }

    /**
     * Build a search query from store address components.
     */
    private function buildQuery(DiscountStore $store): string
    {
        $parts = [];

        if ($store->city) {
            $parts[] = $store->city;
        }

        if ($store->district) {
            $parts[] = $store->district;
        }

        if ($store->address) {
            $parts[] = ($this->normalizeStoreAddress)($store);
        }

        return implode(' ', $parts);
    }
}
