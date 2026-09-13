<?php

declare(strict_types=1);

namespace NouTools\Domains\StudyRoom\ViewModels;

use Spatie\LaravelData\Attributes\DataCollectionOf;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\DataCollection;

final class StudyRoomDailyFocusViewModel extends Data
{
    public function __construct(
        public string $date,
        public string $label,
        public int $focusSeconds,
        #[DataCollectionOf(StudyRoomSessionViewModel::class)]
        public DataCollection $sessions,
    ) {}
}
