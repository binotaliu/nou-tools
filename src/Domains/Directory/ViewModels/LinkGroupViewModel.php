<?php

declare(strict_types=1);

namespace NouTools\Domains\Directory\ViewModels;

use Spatie\LaravelData\Attributes\DataCollectionOf;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\DataCollection;

final class LinkGroupViewModel extends Data
{
    /**
     * @param  DataCollection<int, LinkItemViewModel>  $links
     */
    public function __construct(
        public string $group,
        public string $label,
        #[DataCollectionOf(LinkItemViewModel::class)]
        public DataCollection $links,
    ) {}
}
