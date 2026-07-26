<?php

declare(strict_types=1);

namespace NouTools\Domains\Directory\ViewModels;

use Spatie\LaravelData\Data;

final class LinkItemViewModel extends Data
{
    public function __construct(
        public string $name,
        public string $url,
    ) {}
}
