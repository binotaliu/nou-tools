<?php

namespace NouTools\Domains\Schedules\ViewModels;

use Spatie\LaravelData\Data;

final class AnnouncementSourceViewModel extends Data
{
    /**
     * @param  array<int, string>  $availableCategories
     * @param  array<int, string>  $selectedCategories
     */
    public function __construct(
        public string $source,
        public array $availableCategories,
        public array $selectedCategories,
    ) {}
}
