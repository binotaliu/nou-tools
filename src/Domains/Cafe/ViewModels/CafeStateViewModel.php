<?php

declare(strict_types=1);

namespace NouTools\Domains\Cafe\ViewModels;

use NouTools\Domains\StudyRoom\ViewModels\StudyRoomSeatViewModel;
use Spatie\LaravelData\Data;

final class CafeStateViewModel extends Data
{
    /**
     * @param  array<int, StudyRoomSeatViewModel>  $seats
     */
    public function __construct(
        public array $seats,
        public int $occupantCount,
        public string $serverTime,
        public string $version,
    ) {}
}
