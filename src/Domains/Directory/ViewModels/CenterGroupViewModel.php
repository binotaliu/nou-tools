<?php

declare(strict_types=1);

namespace NouTools\Domains\Directory\ViewModels;

use Spatie\LaravelData\Attributes\DataCollectionOf;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\DataCollection;

final class CenterGroupViewModel extends Data
{
    /**
     * @param  DataCollection<int, CenterItemViewModel>  $centers
     */
    public function __construct(
        public string $label,
        #[DataCollectionOf(CenterItemViewModel::class)]
        public DataCollection $centers,
    ) {}
}
