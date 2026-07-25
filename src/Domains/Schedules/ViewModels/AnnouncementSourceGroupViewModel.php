<?php

namespace NouTools\Domains\Schedules\ViewModels;

use Spatie\LaravelData\Attributes\DataCollectionOf;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\DataCollection;

final class AnnouncementSourceGroupViewModel extends Data
{
    public function __construct(
        public string $group,
        public string $groupLabel,
        #[DataCollectionOf(AnnouncementSourceViewModel::class)]
        public DataCollection $sources,
    ) {}
}
