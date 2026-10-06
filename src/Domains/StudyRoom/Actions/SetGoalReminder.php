<?php

declare(strict_types=1);

namespace NouTools\Domains\StudyRoom\Actions;

use App\Models\StudyRoomProfile;
use NouTools\Domains\Schedules\ValueObjects\StudentScheduleCookie;
use NouTools\Domains\StudyRoom\DataTransferObjects\SetGoalReminderData;
use NouTools\Domains\StudyRoom\Exceptions\NoStudyRoomProfileException;

/**
 * Flips only the goal reminder opt-in. It never unsubscribes the browser:
 * the push subscription is shared with the other notifications.
 */
final readonly class SetGoalReminder
{
    public function __invoke(StudentScheduleCookie $viewer, SetGoalReminderData $data): StudyRoomProfile
    {
        $profile = StudyRoomProfile::query()->where('student_schedule_id', $viewer->id)->first();

        if ($profile === null) {
            throw new NoStudyRoomProfileException;
        }

        $profile->notify_on_goal_reminder = $data->enabled;
        $profile->saveOrFail();

        return $profile;
    }
}
