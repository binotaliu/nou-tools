<?php

declare(strict_types=1);

namespace NouTools\Domains\Cafe\Actions;

use App\Enums\StudyRoomVenue;
use App\Models\StudyRoomSeat;
use Illuminate\Support\Facades\Date;
use NouTools\Domains\Cafe\ViewModels\CafeStateViewModel;
use NouTools\Domains\Schedules\ValueObjects\StudentScheduleCookie;
use NouTools\Domains\StudyRoom\Actions\ResolveSeatVersionSignature;
use NouTools\Domains\StudyRoom\ViewModels\StudyRoomSeatViewModel;

final readonly class BuildCafeState
{
    public function __construct(private ResolveSeatVersionSignature $resolveSeatVersionSignature) {}

    public function __invoke(?StudentScheduleCookie $viewer): CafeStateViewModel
    {
        $seats = StudyRoomSeat::query()
            ->inRoom(StudyRoomVenue::Cafe)
            ->with(['schedule.studyRoomProfile', 'schedule.activeCafeOrder', 'subjectCourse'])
            ->orderBy('id')
            ->get();

        $viewerId = $viewer?->id;
        $viewModels = $seats
            ->map(fn (StudyRoomSeat $seat): StudyRoomSeatViewModel => StudyRoomSeatViewModel::fromModel($seat, $viewerId))
            ->all();

        // `isYou` is viewer-relative and must not move the version, or two
        // viewers of the same room would never share an ETag.
        $signature = $seats
            ->map(fn (StudyRoomSeat $seat): string => ($this->resolveSeatVersionSignature)($seat))
            ->implode(';');

        return new CafeStateViewModel(
            seats: $viewModels,
            occupantCount: $seats->whereNotNull('student_schedule_id')->count(),
            serverTime: Date::now()->toIso8601String(),
            version: hash('xxh128', $signature),
        );
    }
}
