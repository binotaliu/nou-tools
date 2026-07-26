<?php

declare(strict_types=1);

namespace NouTools\Domains\DiscountStores\Actions;

use App\Models\DiscountStore;

final readonly class NormalizeStoreAddress
{
    public function __invoke(DiscountStore $store): string
    {
        $address = trim((string) $store->address);
        $address = str_replace('臺', '台', $address);

        if ($store->city) {
            $city = str_replace('臺', '台', $store->city);

            if (str_starts_with($address, $city)) {
                $address = substr($address, strlen($city));
            }
        }

        if ($store->district) {
            $district = str_replace('臺', '台', $store->district);

            if (str_starts_with($address, $district)) {
                $address = substr($address, strlen($district));
            }
        }

        $address = trim($address);

        if (preg_match('/(.*?[路街](?:[一二三四五六七八九十百零〇0-9]+段)?(?:[0-9]+[巷弄])*)([0-9]+)號/u', $address, $matches)) {
            $roadAndAlleys = $matches[1];
            $doorNumber = $matches[2];

            return trim($roadAndAlleys.' '.$doorNumber);
        }

        return $address;
    }
}
