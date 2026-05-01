<?php

namespace NouTools\Domains\DiscountStores\Actions;

use App\Models\DiscountStore;
use Illuminate\Support\Facades\Http;

final readonly class GeoCodeStoreAddress
{
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
            $response = Http::timeout(10)->get('https://nominatim.openstreetmap.org/search', [
                'format' => 'json',
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
            $parts[] = $this->normalizeAddress($store);
        }

        return implode(' ', $parts);
    }

    /**
     * Normalize address by extracting road name with alleys/lanes and door number.
     * Removes leading city/district if present, then extracts road + alleys + door number.
     *
     * Example: "台北市中正區中山路1巷2弄3號4樓之5" -> "中山路1巷2弄 3"
     * Example: "中山路1巷2弄3號4樓之5" -> "中山路1巷2弄 3"
     */
    private function normalizeAddress(DiscountStore $store): string
    {
        $address = trim($store->address);

        // Remove leading city and district if they exist in the address
        if ($store->city && str_starts_with($address, $store->city)) {
            $address = substr($address, strlen($store->city));
        }

        if ($store->district && str_starts_with($address, $store->district)) {
            $address = substr($address, strlen($store->district));
        }

        $address = trim($address);

        // Extract road + all alleys/lanes + door number
        // Pattern: (road/alleys) + (door number) + 號
        // Matches: 中山路1巷2弄 + 3 + 號, ignoring floors (4樓之5)
        if (preg_match('/(.*?[路街](?:[0-9]+[巷弄])*)([0-9]+)號/', $address, $matches)) {
            $roadAndAlleys = $matches[1];
            $doorNumber = $matches[2];

            return trim($roadAndAlleys.' '.$doorNumber);
        }

        return $address;
    }
}
