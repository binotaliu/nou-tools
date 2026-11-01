<?php

declare(strict_types=1);

namespace NouTools\Domains\Cafe\Actions;

use App\Enums\StudyRoomVenue;
use App\Models\StudyRoomSeat;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\Date;
use NouTools\Domains\StudyRoom\Actions\ReleaseIdleSeats;

/**
 * Asks a student to leave a café seat they sat down at without ordering
 * anything for `cafe.seat.order_grace_seconds`. An order placed since sitting
 * down counts, even if the drink was put away again.
 */
final readonly class ReleaseUnorderedCafeSeats
{
    public function __construct(private ReleaseIdleSeats $releaseIdleSeats) {}

    public function __invoke(): int
    {
        $cutoff = Date::now()->subSeconds((int) config('cafe.seat.order_grace_seconds'));

        $seats = StudyRoomSeat::query()
            ->inRoom(StudyRoomVenue::Cafe)
            ->whereNotNull('student_schedule_id')
            ->where('occupied_at', '<', $cutoff)
            ->whereNotExists(fn (Builder $orders) => $orders
                ->from('cafe_orders')
                ->whereColumn('cafe_orders.student_schedule_id', 'study_room_seats.student_schedule_id')
                ->whereColumn('cafe_orders.created_at', '>=', 'study_room_seats.occupied_at'))
            ->get();

        return $this->releaseIdleSeats->release($seats, 'seat.unordered-released');
    }
}
