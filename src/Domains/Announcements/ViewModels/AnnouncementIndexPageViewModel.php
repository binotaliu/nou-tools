<?php

namespace NouTools\Domains\Announcements\ViewModels;

use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

final readonly class AnnouncementIndexPageViewModel
{
    /**
     * @param  Collection<int, string>  $availableSources
     * @param  Collection<int, string>  $availableCategories
     * @param  Collection<string, Collection<int, string>>  $sourceCategories
     * @param  array<string, array<int, string>>  $selectedSourceCategories
     */
    public function __construct(
        public LengthAwarePaginator $announcements,
        public Collection $availableSources,
        public Collection $availableCategories,
        public Collection $sourceCategories,
        /** @var array<int, string> */
        public array $selectedSources,
        public array $selectedSourceCategories,
        public int $totalAnnouncements,
    ) {}
}
