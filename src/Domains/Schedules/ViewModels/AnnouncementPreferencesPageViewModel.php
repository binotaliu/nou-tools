<?php

namespace NouTools\Domains\Schedules\ViewModels;

use App\Enums\AnnouncementSourceGroup;
use App\Models\StudentSchedule;
use Illuminate\Support\Collection;

final readonly class AnnouncementPreferencesPageViewModel
{
    /**
     * @param  Collection<string, Collection<string, Collection<int, string>>>  $groupedCatalog
     * @param  array<string, array<int, string>>  $selectedSourceCategories
     */
    public function __construct(
        public StudentSchedule $schedule,
        public Collection $groupedCatalog,
        public array $selectedSourceCategories,
    ) {}

    /**
     * @param  array<string, array<int, string>>|null  $stored
     * @param  Collection<string, Collection<int, string>>  $flatCatalog
     * @param  Collection<string, Collection<string, Collection<int, string>>>  $groupedCatalog
     * @return array<string, array<int, string>>
     */
    public static function normalizeSelectedSourceCategories(?array $stored, Collection $flatCatalog, Collection $groupedCatalog): array
    {
        if ($stored === null) {
            return $groupedCatalog
                ->get(AnnouncementSourceGroup::Administrative->value, collect())
                ->map(fn (Collection $categories): array => $categories->all())
                ->all();
        }

        if ($stored === []) {
            return [];
        }

        return collect($stored)
            ->filter(fn ($categories, $source): bool => is_string($source) && $flatCatalog->has($source))
            ->map(function ($categories, string $source) use ($flatCatalog): array {
                $availableCategories = $flatCatalog->get($source, collect());

                return collect(is_array($categories) ? $categories : [])
                    ->intersect($availableCategories)
                    ->values()
                    ->all();
            })
            ->filter(fn (array $categories): bool => $categories !== [])
            ->all();
    }
}
