<?php

declare(strict_types=1);

namespace NouTools\Domains\Directory\Actions;

use App\Enums\CenterRegion;
use Illuminate\Support\Collection;

final readonly class ListCentersInDirectoryOrder
{
    /**
     * Configured centers ordered by region, then by their order in `directory.centers`.
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function __invoke(): Collection
    {
        $regionOrder = collect(CenterRegion::cases())
            ->map(fn (CenterRegion $region): string => $region->value)
            ->flip();

        return collect(config('directory.centers', []))
            ->sortBy(fn (array $center): int => $regionOrder[$center['region']] ?? PHP_INT_MAX)
            ->values();
    }
}
