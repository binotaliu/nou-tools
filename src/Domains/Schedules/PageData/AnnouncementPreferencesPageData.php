<?php

namespace NouTools\Domains\Schedules\PageData;

use App\Enums\AnnouncementSourceGroup;
use App\Models\StudentSchedule;
use Illuminate\Support\Collection;
use NouTools\Domains\Schedules\ViewModels\AnnouncementSourceGroupViewModel;
use NouTools\Domains\Schedules\ViewModels\AnnouncementSourceViewModel;
use Spatie\LaravelData\Attributes\DataCollectionOf;
use Spatie\LaravelData\DataCollection;
use Spatie\LaravelData\Resource;

final class AnnouncementPreferencesPageData extends Resource
{
    public function __construct(
        public StudentSchedule $schedule,
        #[DataCollectionOf(AnnouncementSourceGroupViewModel::class)]
        public DataCollection $sourceGroups,
    ) {}

    /**
     * @param  Collection<string, Collection<string, Collection<int, string>>>  $groupedCatalog
     * @param  array<string, array<int, string>>  $selectedSourceCategories
     */
    public static function fromCatalog(StudentSchedule $schedule, Collection $groupedCatalog, array $selectedSourceCategories): self
    {
        $groups = $groupedCatalog
            ->map(function (Collection $sources, string $group) use ($selectedSourceCategories): AnnouncementSourceGroupViewModel {
                $sourceViewModels = $sources
                    ->map(function (Collection $categories, string $source) use ($selectedSourceCategories): AnnouncementSourceViewModel {
                        return new AnnouncementSourceViewModel(
                            source: $source,
                            availableCategories: $categories->values()->all(),
                            selectedCategories: $selectedSourceCategories[$source] ?? [],
                        );
                    })
                    ->values();

                return new AnnouncementSourceGroupViewModel(
                    group: $group,
                    groupLabel: AnnouncementSourceGroup::from($group)->label(),
                    sources: AnnouncementSourceViewModel::collect($sourceViewModels, DataCollection::class),
                );
            })
            ->values();

        return new self(
            schedule: $schedule,
            sourceGroups: AnnouncementSourceGroupViewModel::collect($groups, DataCollection::class),
        );
    }

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
