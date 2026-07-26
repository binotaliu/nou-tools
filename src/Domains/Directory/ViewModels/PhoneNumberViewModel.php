<?php

declare(strict_types=1);

namespace NouTools\Domains\Directory\ViewModels;

use Spatie\LaravelData\Data;

final class PhoneNumberViewModel extends Data
{
    public function __construct(
        public string $display,
        public string $link,
    ) {}
}
