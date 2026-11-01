<?php

declare(strict_types=1);

namespace NouTools\Domains\Cafe\ViewModels;

use Spatie\LaravelData\Data;

final class CafeStateViewModel extends Data
{
    /**
     * @param  array<int, CafeSeatViewModel>  $seats
     */
    public function __construct(
        public array $seats,
        public int $occupantCount,
        public string $serverTime,
        public string $version,
    ) {}
}
