<?php

declare(strict_types=1);

namespace NouTools\Domains\Home\DataTransferObjects;

use Spatie\LaravelData\Data;

final class ShowHomePageData extends Data
{
    public function __construct(
        public ?string $date = null,
    ) {}
}
