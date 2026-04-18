<?php

namespace NouTools\Domains\Announcements\Actions;

use App\Models\Announcement;
use Illuminate\Support\Collection;
use NouTools\Domains\Announcements\DataTransferObjects\ShowAnnouncementIndexPageData;
use NouTools\Domains\Announcements\ViewModels\AnnouncementIndexPageViewModel;

final readonly class ShowAnnouncementIndexPage
{
    public function __invoke(ShowAnnouncementIndexPageData $input): AnnouncementIndexPageViewModel
    {
        $configuredSourceCategories = $this->configuredSourceCategories();
        $availableSources = $configuredSourceCategories->keys()->sort()->values();
        $selectedSourceCategories = $this->selectedSourceCategoriesFromInput($input, $configuredSourceCategories);
        $selectedSources = collect(array_keys($selectedSourceCategories))->sort()->values()->all();
        $availableCategories = $this->availableCategoriesForSources($selectedSources, $configuredSourceCategories);

        $announcements = Announcement::query()
            ->when($selectedSourceCategories !== [], function ($query) use ($selectedSourceCategories) {
                $query->where(function ($subQuery) use ($selectedSourceCategories) {
                    foreach ($selectedSourceCategories as $source => $categories) {
                        $subQuery->orWhere(function ($sourceCategoryQuery) use ($source, $categories) {
                            $sourceCategoryQuery
                                ->where('source_name', $source)
                                ->whereIn('category', $categories);
                        });
                    }
                });
            })
            ->orderByDesc('published_at')
            ->orderByDesc('fetched_at')
            ->orderByDesc('id')
            ->paginate(30)
            ->withQueryString();

        return new AnnouncementIndexPageViewModel(
            announcements: $announcements,
            availableSources: $availableSources,
            availableCategories: $availableCategories,
            sourceCategories: $configuredSourceCategories,
            selectedSources: $selectedSources,
            selectedSourceCategories: $selectedSourceCategories,
            totalAnnouncements: Announcement::query()->count(),
        );
    }

    /**
     * @return Collection<string, Collection<int, string>>
     */
    private function configuredSourceCategories(): Collection
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

    /**
     * @param  Collection<string, Collection<int, string>>  $configuredSourceCategories
     * @return array<string, array<int, string>>
     */
    private function selectedSourceCategoriesFromInput(ShowAnnouncementIndexPageData $input, Collection $configuredSourceCategories): array
    {
        $selectedSourceCategories = $this->sanitizeSelectedSourceCategories(
            $this->normalizeSourceCategoryFilter($input->sourceCategories),
            $configuredSourceCategories,
        );

        if ($selectedSourceCategories !== []) {
            return $selectedSourceCategories;
        }

        return $this->legacySelectedSourceCategories($input, $configuredSourceCategories);
    }

    /**
     * @param  Collection<string, Collection<int, string>>  $sourceCategories
     * @return array<string, array<int, string>>
     */
    private function sanitizeSelectedSourceCategories(Collection $selectedSourceCategories, Collection $sourceCategories): array
    {
        return $selectedSourceCategories
            ->map(function (Collection $categories, string $source) use ($sourceCategories): Collection {
                return $categories->intersect($sourceCategories->get($source, collect()))->values();
            })
            ->filter(fn (Collection $categories): bool => $categories->isNotEmpty())
            ->map(fn (Collection $categories): array => $categories->all())
            ->all();
    }

    /**
     * @param  Collection<string, Collection<int, string>>  $sourceCategories
     * @return array<string, array<int, string>>
     */
    private function legacySelectedSourceCategories(ShowAnnouncementIndexPageData $input, Collection $sourceCategories): array
    {
        $configuredSources = $sourceCategories->keys();
        $selectedSources = $this->normalizeSourceFilter($input->source)
            ->intersect($configuredSources)
            ->values();
        $selectedCategories = $this->normalizeCategoryFilter($input->category);

        if ($selectedSources->isEmpty() && $selectedCategories->isEmpty()) {
            return [];
        }

        $sourcesToFilter = $selectedSources->isEmpty() ? $configuredSources : $selectedSources;

        return $sourcesToFilter
            ->mapWithKeys(function (string $source) use ($sourceCategories, $selectedCategories): array {
                $availableCategories = $sourceCategories->get($source, collect());
                $categories = $selectedCategories->isEmpty()
                    ? $availableCategories
                    : $availableCategories->intersect($selectedCategories)->values();

                return $categories->isEmpty() ? [] : [$source => $categories->all()];
            })
            ->all();
    }

    /**
     * @param  array<int, string>  $selectedSources
     * @param  Collection<string, Collection<int, string>>  $sourceCategories
     * @return Collection<int, string>
     */
    private function availableCategoriesForSources(array $selectedSources, Collection $sourceCategories): Collection
    {
        if ($selectedSources === []) {
            return $sourceCategories->flatten(1)->unique()->sort()->values();
        }

        return collect($selectedSources)
            ->flatMap(fn (string $sourceName): Collection => $sourceCategories->get($sourceName, collect()))
            ->unique()
            ->sort()
            ->values();
    }

    private function normalizeFilter(?string $value): ?string
    {
        $value = $value !== null ? trim($value) : null;

        return $value === '' ? null : $value;
    }

    /**
     * @return Collection<int, string>
     */
    private function normalizeCategoryFilter(string|array|null $value): Collection
    {
        $categories = is_array($value) ? $value : [$value];

        return collect($categories)
            ->map(fn (?string $category): ?string => $category !== null ? trim($category) : null)
            ->filter(fn (?string $category): bool => $category !== null && $category !== '')
            ->unique()
            ->values();
    }

    /**
     * @return Collection<string, Collection<int, string>>
     */
    private function normalizeSourceCategoryFilter(array|string|null $value): Collection
    {
        if (! is_array($value)) {
            return collect();
        }

        return collect($value)
            ->filter(function (mixed $categories, mixed $source): bool {
                return is_string($source) && trim($source) !== '';
            })
            ->mapWithKeys(function (mixed $categories, string $source): array {
                $normalizedSource = trim($source);
                $normalizedCategories = collect(is_array($categories) ? $categories : [$categories])
                    ->map(fn (mixed $category): ?string => is_string($category) ? trim($category) : null)
                    ->filter(fn (?string $category): bool => $category !== null && $category !== '')
                    ->unique()
                    ->values();

                return $normalizedCategories->isEmpty() ? [] : [$normalizedSource => $normalizedCategories];
            });
    }

    /**
     * @return Collection<int, string>
     */
    private function normalizeSourceFilter(string|array|null $value): Collection
    {
        $sources = is_array($value) ? $value : [$value];

        return collect($sources)
            ->map(fn (?string $source): ?string => $source !== null ? trim($source) : null)
            ->filter(fn (?string $source): bool => $source !== null && $source !== '')
            ->unique()
            ->values();
    }
}
