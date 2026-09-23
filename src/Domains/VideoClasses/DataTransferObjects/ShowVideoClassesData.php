<?php

declare(strict_types=1);

namespace NouTools\Domains\VideoClasses\DataTransferObjects;

use Spatie\LaravelData\Data;

final class ShowVideoClassesData extends Data
{
    public function __construct(
        public ?string $date = null,
    ) {}
}
