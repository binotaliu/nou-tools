<?php

namespace NouTools\Domains\Announcements\Actions;

use Illuminate\Support\Collection;

final readonly class ListAnnouncementSourceCategories
{
    /**
     * @return Collection<string, Collection<int, string>>
     */
    public function __invoke(): Collection
    {
        return collect(config('announcements.sources', []))
            ->filter(fn (array $source): bool => (bool) ($source['is_active'] ?? false))
            ->map(function (array $source): array {
                return [
                    'name' => trim((string) ($source['name'] ?? '')),
                    'category' => trim((string) ($source['category'] ?? '')),
                ];
            })
            ->filter(fn (array $source): bool => $source['name'] !== '' && $source['category'] !== '')
            ->groupBy('name')
            ->map(function (Collection $sources): Collection {
                return $sources->pluck('category')->unique()->sort()->values();
            });
    }
}
