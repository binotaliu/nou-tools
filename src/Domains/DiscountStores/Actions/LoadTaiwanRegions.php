<?php

declare(strict_types=1);

namespace NouTools\Domains\DiscountStores\Actions;

use Illuminate\Support\Facades\File;
use RuntimeException;

final readonly class LoadTaiwanRegions
{
    /**
     * @return array<int, array{name: string, districts: array<int, array{name: string, zip?: string}>}>
     */
    public function __invoke(): array
    {
        $path = resource_path('data/taiwan-regions.json');

        if (! File::exists($path)) {
            throw new RuntimeException("Taiwan regions file not found at {$path}");
        }

        $data = json_decode(File::get($path), true, 512, JSON_THROW_ON_ERROR);

        if (! is_array($data)) {
            throw new RuntimeException('Taiwan regions JSON has invalid structure.');
        }

        return $data;
    }
}
