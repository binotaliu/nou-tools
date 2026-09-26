<?php

declare(strict_types=1);

namespace NouTools\Domains\Announcements\ViewModels;

use App\Enums\AnnouncementSourceGroup;
use Spatie\LaravelData\Data;

final class SourceCategorySelectionViewModel extends Data
{
    /**
     * @param  array<int, string>  $availableCategories
     * @param  array<int, string>  $selectedCategories
     */
    public function __construct(
        public string $source,
        public AnnouncementSourceGroup $group,
        public string $groupLabel,
        public array $availableCategories,
        public array $selectedCategories,
    ) {}
}
