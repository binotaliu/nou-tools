<?php

declare(strict_types=1);

namespace NouTools\Domains\StudyRoom\Actions;

use App\Models\StudyRoomSeat;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Date;

/**
 * Idempotently reconciles `study_room_seats` against `BuildSeatDefinitions`,
 * keyed on `code`. Never touches occupancy columns, and never evicts a
 * seat that's currently occupied even if the layout shrinks around it.
 */
final readonly class SyncStudyRoomSeats
{
    public function __construct(
        private BuildSeatDefinitions $buildSeatDefinitions,
    ) {}

    public function __invoke(): int
    {
        return Cache::lock('study-room:sync-seats', 10)->block(5, function (): int {
            $definitions = ($this->buildSeatDefinitions)();
            $now = Date::now();

            $rows = array_map(fn (array $definition): array => [
                'floor' => $definition['floor'],
                'kind' => $definition['kind']->value,
                'group_code' => $definition['group_code'],
                'seat_number' => $definition['seat_number'],
                'code' => $definition['code'],
                'label' => $definition['label'],
                'created_at' => $now,
                'updated_at' => $now,
            ], $definitions);

            StudyRoomSeat::query()->upsert(
                $rows,
                ['code'],
                ['floor', 'kind', 'group_code', 'seat_number', 'label', 'updated_at'],
            );

            StudyRoomSeat::query()
                ->whereNotIn('code', array_column($definitions, 'code'))
                ->whereNull('student_schedule_id')
                ->delete();

            return StudyRoomSeat::query()->count();
        });
    }
}
