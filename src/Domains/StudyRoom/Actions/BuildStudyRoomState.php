<?php

declare(strict_types=1);

namespace NouTools\Domains\StudyRoom\Actions;

use App\Enums\StudySeatKind;
use App\Models\StudyRoomSeat;
use App\Models\StudyRoomSession;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Date;
use NouTools\Domains\Schedules\ValueObjects\StudentScheduleCookie;
use NouTools\Domains\StudyRoom\ViewModels\StudyRoomFloorViewModel;
use NouTools\Domains\StudyRoom\ViewModels\StudyRoomSeatViewModel;
use NouTools\Domains\StudyRoom\ViewModels\StudyRoomStateViewModel;
use NouTools\Domains\StudyRoom\ViewModels\StudyRoomTableViewModel;
use NouTools\Domains\StudyRoom\ViewModels\StudyRoomTotalsViewModel;
use Spatie\LaravelData\DataCollection;

final readonly class BuildStudyRoomState
{
    public function __construct(
        private ResolveOpenFloorCount $resolveOpenFloorCount,
    ) {}

    public function __invoke(?StudentScheduleCookie $viewer): StudyRoomStateViewModel
    {
        $openFloors = ($this->resolveOpenFloorCount)();

        $seats = StudyRoomSeat::query()
            ->where('floor', '<=', $openFloors)
            ->with(['schedule.studyRoomProfile', 'subjectCourse'])
            ->orderBy('floor')
            ->orderBy('seat_number')
            ->get();

        $floors = $this->buildFloors($seats, $openFloors, $viewer?->id);

        return new StudyRoomStateViewModel(
            floors: StudyRoomFloorViewModel::collect($floors, DataCollection::class),
            openFloors: $openFloors,
            totals: $this->buildTotals($seats, $viewer),
            serverTime: Date::now()->toIso8601String(),
            version: $this->resolveVersion($seats),
        );
    }

    /**
     * @param  Collection<int, StudyRoomSeat>  $seats
     * @return array<int, StudyRoomFloorViewModel>
     */
    private function buildFloors(Collection $seats, int $openFloors, ?int $viewerScheduleId): array
    {
        $floors = [];

        for ($floor = 1; $floor <= $openFloors; $floor++) {
            $floorSeats = $seats->where('floor', $floor);

            $soloSeats = $floorSeats
                ->where('kind', StudySeatKind::Solo)
                ->sortBy('seat_number')
                ->map(fn (StudyRoomSeat $seat) => StudyRoomSeatViewModel::fromModel($seat, $viewerScheduleId))
                ->values()
                ->all();

            $tables = $floorSeats
                ->where('kind', StudySeatKind::Shared)
                ->groupBy('group_code')
                ->sortKeys()
                ->map(function (Collection $tableSeats, string $groupCode) use ($viewerScheduleId): StudyRoomTableViewModel {
                    $seatViewModels = $tableSeats
                        ->sortBy('seat_number')
                        ->map(fn (StudyRoomSeat $seat) => StudyRoomSeatViewModel::fromModel($seat, $viewerScheduleId));

                    return StudyRoomTableViewModel::fromSeats($groupCode, $seatViewModels);
                })
                ->values()
                ->all();

            $floors[] = StudyRoomFloorViewModel::make(
                floor: $floor,
                soloSeats: $soloSeats,
                tables: $tables,
                occupiedCount: $floorSeats->whereNotNull('student_schedule_id')->count(),
                totalCount: $floorSeats->count(),
            );
        }

        return $floors;
    }

    /**
     * @param  Collection<int, StudyRoomSeat>  $seats
     */
    private function buildTotals(Collection $seats, ?StudentScheduleCookie $viewer): StudyRoomTotalsViewModel
    {
        $timezone = config('app.schedule_timezone');
        $startOfDayUtc = Date::now($timezone)->startOfDay()->utc();
        $endOfDayUtc = Date::now($timezone)->endOfDay()->utc();

        $siteFocusSecondsToday = (int) StudyRoomSession::query()
            ->whereBetween('ended_at', [$startOfDayUtc, $endOfDayUtc])
            ->sum('focus_seconds');

        $yourFocusSecondsToday = $viewer === null ? 0 : (int) StudyRoomSession::query()
            ->where('student_schedule_id', $viewer->id)
            ->whereBetween('ended_at', [$startOfDayUtc, $endOfDayUtc])
            ->sum('focus_seconds');

        return new StudyRoomTotalsViewModel(
            occupantCount: $seats->whereNotNull('student_schedule_id')->count(),
            siteFocusSecondsToday: $siteFocusSecondsToday,
            yourFocusSecondsToday: $yourFocusSecondsToday,
        );
    }

    /**
     * A content hash of everything the client renders, not a timestamp.
     *
     * `updated_at` only has second resolution, so two seat changes landing
     * in the same wall-clock second produce an identical value. A client
     * that polled between them would then send a matching If-None-Match
     * and be told 304 forever, leaving the room permanently stale on its
     * screen. Hashing the state itself cannot collide that way.
     *
     * @param  Collection<int, StudyRoomSeat>  $seats
     */
    private function resolveVersion(Collection $seats): string
    {
        $signature = $seats
            ->sortBy('id')
            ->map(fn (StudyRoomSeat $seat): string => implode('|', [
                $seat->id,
                $seat->student_schedule_id ?? '',
                $seat->activity_verb?->value ?? '',
                $seat->subject_course_id ?? '',
                $seat->subject_label ?? '',
                $seat->timer_mode?->value ?? '',
                $seat->timer_phase?->value ?? '',
                $seat->timer_round ?? '',
                $seat->timer_started_at?->getTimestamp() ?? '',
                $seat->timer_ends_at?->getTimestamp() ?? '',
            ]))
            ->implode(';');

        return hash('xxh128', $signature);
    }
}
