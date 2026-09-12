<?php

declare(strict_types=1);

namespace NouTools\Domains\StudyRoom\ViewModels;

use Illuminate\Support\Str;
use Spatie\LaravelData\Attributes\DataCollectionOf;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\DataCollection;

final class StudyRoomFloorViewModel extends Data
{
    public function __construct(
        public int $floor,
        public string $label,
        #[DataCollectionOf(StudyRoomSeatViewModel::class)]
        public DataCollection $soloSeats,
        #[DataCollectionOf(StudyRoomTableViewModel::class)]
        public DataCollection $tables,
        public int $occupiedCount,
        public int $totalCount,
    ) {}

    /**
     * @param  array<int, StudyRoomSeatViewModel>  $soloSeats
     * @param  array<int, StudyRoomTableViewModel>  $tables
     */
    public static function make(int $floor, array $soloSeats, array $tables, int $occupiedCount, int $totalCount): self
    {
        return new self(
            floor: $floor,
            label: Str::toChineseNumber($floor).'樓',
            soloSeats: StudyRoomSeatViewModel::collect($soloSeats, DataCollection::class),
            tables: StudyRoomTableViewModel::collect($tables, DataCollection::class),
            occupiedCount: $occupiedCount,
            totalCount: $totalCount,
        );
    }
}
