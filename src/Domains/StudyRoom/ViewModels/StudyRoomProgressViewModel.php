<?php

declare(strict_types=1);

namespace NouTools\Domains\StudyRoom\ViewModels;

use Spatie\LaravelData\Attributes\DataCollectionOf;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\DataCollection;

final class StudyRoomProgressViewModel extends Data
{
    /**
     * @param  array<int, string>  $activeDates  Taipei dates (Y-m-d) in the calendar grid that have records.
     */
    public function __construct(
        public string $today,
        public string $month,
        public string $gridStart,
        public string $weekStart,
        public bool $includeInPerson,
        public array $activeDates,
        public int $weekFocusSeconds,
        #[DataCollectionOf(StudyRoomDailyFocusViewModel::class)]
        public DataCollection $days,
        #[DataCollectionOf(StudyRoomSubjectShareViewModel::class)]
        public DataCollection $subjects,
    ) {}
}
