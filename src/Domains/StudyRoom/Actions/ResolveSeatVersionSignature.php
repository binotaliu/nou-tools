<?php

declare(strict_types=1);

namespace NouTools\Domains\StudyRoom\Actions;

use App\Models\StudyRoomSeat;

/**
 * Everything about one seat that the client renders, as a string for the
 * state version hash. Shared by the study room and the café so a field added
 * to the seat view model is only forgotten in one place. Needs `schedule`
 * (with `studyRoomProfile` and `activeCafeOrder`) loaded.
 */
final readonly class ResolveSeatVersionSignature
{
    public function __invoke(StudyRoomSeat $seat): string
    {
        $order = $seat->schedule?->activeCafeOrder;
        $profile = $seat->schedule?->studyRoomProfile;

        return implode('|', [
            $seat->id,
            $seat->student_schedule_id ?? '',
            $profile?->nickname ?? '',
            $profile?->emoji ?? '',
            $order?->id ?? '',
            $order?->ready_at?->getTimestamp() ?? '',
            $order?->sips_taken ?? '',
            $seat->activity_verb?->value ?? '',
            $seat->subject_course_id ?? '',
            $seat->subject_label ?? '',
            $seat->timer_mode?->value ?? '',
            $seat->timer_phase?->value ?? '',
            $seat->timer_round ?? '',
            $seat->timer_started_at?->getTimestamp() ?? '',
            $seat->timer_ends_at?->getTimestamp() ?? '',
            $seat->paused_at?->getTimestamp() ?? '',
        ]);
    }
}
