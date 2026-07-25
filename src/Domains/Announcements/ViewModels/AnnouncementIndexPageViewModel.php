<?php

namespace NouTools\Domains\Announcements\ViewModels;

use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Spatie\LaravelData\Attributes\DataCollectionOf;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\DataCollection;

final class AnnouncementIndexPageViewModel extends Data
{
    /**
     * @param  Collection<int, string>  $availableSources
     * @param  Collection<int, string>  $availableCategories
     * @param  DataCollection<int, SourceCategorySelectionViewModel>  $sourceCategorySelections
     * @param  array<int, string>  $selectedSources
     */
    public function __construct(
        public LengthAwarePaginator $announcements,
        public Collection $availableSources,
        public Collection $availableCategories,
        #[DataCollectionOf(SourceCategorySelectionViewModel::class)]
        public DataCollection $sourceCategorySelections,
        public array $selectedSources,
        public int $totalAnnouncements,
    ) {}
}
