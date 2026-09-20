<?php

declare(strict_types=1);

namespace NouTools\Domains\StudyRoom\Actions;

use App\Models\StudyRoomProfile;
use NouTools\Domains\Schedules\ValueObjects\StudentScheduleCookie;
use NouTools\Domains\StudyRoom\DataTransferObjects\SetTimerEndNotificationData;
use NouTools\Domains\StudyRoom\Exceptions\NoStudyRoomProfileException;

/**
 * Flips only the timer-end notification opt-in, for callers that have no
 * nickname form to go through (the 設定 page). It never creates a profile:
 * that needs a nickname, which 自習室 asks for.
 */
final readonly class SetTimerEndNotification
{
    public function __invoke(StudentScheduleCookie $viewer, SetTimerEndNotificationData $data): void
    {
        $profile = StudyRoomProfile::query()->where('student_schedule_id', $viewer->id)->first();

        if ($profile === null) {
            throw new NoStudyRoomProfileException;
        }

        $profile->notify_on_timer_end = $data->enabled;
        $profile->saveOrFail();
    }
}
