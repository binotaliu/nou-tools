<?php

declare(strict_types=1);

namespace NouTools\Domains\Cafe\Actions;

use App\Enums\StudyRoomVenue;
use App\Models\StudyRoomSeat;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Date;

/**
 * Idempotently reconciles café rows of `study_room_seats` against the layout, keyed on `code`.
 * Never touches occupancy, and never evicts an occupied seat.
 */
final readonly class SyncCafeSeats
{
    public function __construct(private BuildCafeSeatDefinitions $buildCafeSeatDefinitions) {}

    public function __invoke(): int
    {
        return Cache::lock('cafe:sync-seats', 10)->block(5, function (): int {
            $definitions = ($this->buildCafeSeatDefinitions)();
            $now = Date::now();

            StudyRoomSeat::query()->upsert(
                array_map(fn (array $definition): array => [
                    'room' => StudyRoomVenue::Cafe->value,
                    'floor' => 0,
                    'kind' => $definition['kind']->value,
                    'group_code' => $definition['group_code'],
                    'seat_number' => $definition['seat_number'],
                    'code' => $definition['code'],
                    'label' => $definition['label'],
                    'created_at' => $now,
                    'updated_at' => $now,
                ], $definitions),
                ['code'],
                ['room', 'floor', 'kind', 'group_code', 'seat_number', 'label', 'updated_at'],
            );

            StudyRoomSeat::query()
                ->inRoom(StudyRoomVenue::Cafe)
                ->whereNotIn('code', array_column($definitions, 'code'))
                ->whereNull('student_schedule_id')
                ->delete();

            return StudyRoomSeat::query()->inRoom(StudyRoomVenue::Cafe)->count();
        });
    }
}
