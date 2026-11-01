<?php

declare(strict_types=1);

namespace NouTools\Domains\StudyRoom\Actions;

use App\Models\StudyRoomSeat;
use NouTools\Domains\Cafe\Actions\BuildCafeState;
use NouTools\Domains\Cafe\ViewModels\CafeStateViewModel;
use NouTools\Domains\Schedules\ValueObjects\StudentScheduleCookie;
use NouTools\Domains\StudyRoom\ViewModels\StudyRoomStateViewModel;

/**
 * The state of the room the viewer is sitting in. The focus timer endpoints
 * are shared by the study room and the café (a café seat is a study-room seat
 * in another room), so they answer with the state the calling page renders.
 */
final readonly class BuildHeldRoomState
{
    public function __construct(
        private BuildStudyRoomState $buildStudyRoomState,
        private BuildCafeState $buildCafeState,
    ) {}

    public function __invoke(StudentScheduleCookie $viewer): StudyRoomStateViewModel|CafeStateViewModel
    {
        $seat = StudyRoomSeat::query()
            ->where('student_schedule_id', $viewer->id)
            ->first();

        return $seat?->isCafe()
            ? ($this->buildCafeState)($viewer)
            : ($this->buildStudyRoomState)($viewer);
    }
}
