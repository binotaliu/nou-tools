<?php

declare(strict_types=1);

namespace NouTools\Domains\StudyRoom\Actions;

use App\Models\StudyRoomProfile;
use NouTools\Domains\Schedules\ValueObjects\StudentScheduleCookie;
use NouTools\Domains\StudyRoom\DataTransferObjects\SetStudyGoalData;
use NouTools\Domains\StudyRoom\Exceptions\NoStudyRoomProfileException;

/**
 * Saves the weekly goal and the per-weekday goals. It never creates a
 * profile: that needs a nickname, which 自習室 asks for.
 */
final readonly class SetStudyGoal
{
    public function __invoke(StudentScheduleCookie $viewer, SetStudyGoalData $data): StudyRoomProfile
    {
        $profile = StudyRoomProfile::query()->where('student_schedule_id', $viewer->id)->first();

        if ($profile === null) {
            throw new NoStudyRoomProfileException;
        }

        $dailyGoals = [];

        for ($weekday = 1; $weekday <= 7; $weekday++) {
            $goal = $data->dailyGoals[$weekday] ?? $data->dailyGoals[(string) $weekday] ?? [];
            $minutes = $goal['minutes'] ?? null;
            $remindAt = $goal['remindAt'] ?? null;

            if ($minutes === null && $remindAt === null) {
                continue;
            }

            $dailyGoals[$weekday] = ['minutes' => $minutes, 'remindAt' => $remindAt];
        }

        $profile->weekly_goal_minutes = $data->weeklyGoalMinutes;
        $profile->goal_excludes_in_person_class = $data->excludeInPersonClass;
        $profile->daily_goals = $dailyGoals === [] ? null : $dailyGoals;
        $profile->saveOrFail();

        return $profile;
    }
}
