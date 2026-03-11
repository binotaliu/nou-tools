<?php

namespace NouTools\Domains\Home\DataTransferObjects;

use Spatie\LaravelData\Data;

final class ShowHomePageData extends Data
{
    public function __construct(
        public ?string $date = null,
    ) {}
}
