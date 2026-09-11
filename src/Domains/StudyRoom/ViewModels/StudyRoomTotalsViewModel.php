<?php

declare(strict_types=1);

namespace NouTools\Domains\StudyRoom\ViewModels;

use Spatie\LaravelData\Data;

final class StudyRoomTotalsViewModel extends Data
{
    public function __construct(
        public int $occupantCount,
        public int $siteFocusSecondsToday,
        public int $yourFocusSecondsToday,
    ) {}
}
