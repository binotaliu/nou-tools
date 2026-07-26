<?php

declare(strict_types=1);

namespace NouTools\Domains\Announcements\PageData;

use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use NouTools\Domains\Announcements\ViewModels\SourceCategorySelectionViewModel;
use Spatie\LaravelData\Attributes\DataCollectionOf;
use Spatie\LaravelData\DataCollection;
use Spatie\LaravelData\Resource;

final class AnnouncementIndexPageData extends Resource
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
