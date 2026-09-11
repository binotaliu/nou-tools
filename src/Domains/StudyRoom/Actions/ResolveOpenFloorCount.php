<?php

declare(strict_types=1);

namespace NouTools\Domains\StudyRoom\Actions;

use App\Models\StudyRoomSeat;

/**
 * Floors are derived, never stored. One aggregate query over
 * `study_room_seats`, then:
 *
 *   highestOccupied = max floor where occupied > 0        (0 when the room is empty)
 *   prefixFull      = largest k such that floors 1..k are ALL fully occupied   (0 when floor 1 isn't full)
 *   openFloors      = min(floors.max, max(1, highestOccupied, prefixFull + 1))
 *
 * This gives, for free: floor 1 is always open; when all open floors are
 * full the next floor opens; a floor with anyone on it never closes; an
 * emptied top floor disappears on its own. No state, no cleanup job, no
 * race.
 */
final readonly class ResolveOpenFloorCount
{
    public function __invoke(): int
    {
        $floorCounts = StudyRoomSeat::query()
            ->selectRaw('floor, COUNT(*) as total, COUNT(student_schedule_id) as occupied')
            ->groupBy('floor')
            ->orderBy('floor')
            ->get();

        $highestOccupied = 0;
        $prefixFull = 0;
        $expectedFloor = 1;
        $stillContiguousPrefix = true;

        foreach ($floorCounts as $floorCount) {
            $floor = (int) $floorCount->floor;
            $total = (int) $floorCount->total;
            $occupied = (int) $floorCount->occupied;

            if ($occupied > 0) {
                $highestOccupied = max($highestOccupied, $floor);
            }

            if ($stillContiguousPrefix && $floor === $expectedFloor && $total > 0 && $occupied === $total) {
                $prefixFull = $floor;
                $expectedFloor++;
            } else {
                $stillContiguousPrefix = false;
            }
        }

        return min(
            (int) config('study-room.floors.max'),
            max(1, $highestOccupied, $prefixFull + 1),
        );
    }
}
