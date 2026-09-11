<?php

declare(strict_types=1);

namespace NouTools\Domains\StudyRoom\ViewModels;

use Spatie\LaravelData\Attributes\DataCollectionOf;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\DataCollection;

final class StudyRoomStateViewModel extends Data
{
    public function __construct(
        #[DataCollectionOf(StudyRoomFloorViewModel::class)]
        public DataCollection $floors,
        public int $openFloors,
        public StudyRoomTotalsViewModel $totals,
        public string $serverTime,
        public string $version,
    ) {}
}
