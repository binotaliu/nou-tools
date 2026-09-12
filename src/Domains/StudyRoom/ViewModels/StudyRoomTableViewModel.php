<?php

declare(strict_types=1);

namespace NouTools\Domains\StudyRoom\ViewModels;

use Illuminate\Support\Collection;
use Spatie\LaravelData\Attributes\DataCollectionOf;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\DataCollection;

final class StudyRoomTableViewModel extends Data
{
    public function __construct(
        public string $groupCode,
        public string $label,
        #[DataCollectionOf(StudyRoomSeatViewModel::class)]
        public DataCollection $seats,
    ) {}

    /**
     * @param  Collection<int, StudyRoomSeatViewModel>  $seats
     */
    public static function fromSeats(string $groupCode, Collection $seats): self
    {
        $tableNumber = ltrim($groupCode, 'T');

        return new self(
            groupCode: $groupCode,
            label: "{$tableNumber} 號桌",
            seats: StudyRoomSeatViewModel::collect($seats->values(), DataCollection::class),
        );
    }
}
