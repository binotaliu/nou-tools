<?php

declare(strict_types=1);

namespace NouTools\Domains\StudyRoom\ViewModels;

use App\Models\StudyRoomProfile;
use Spatie\LaravelData\Data;

/**
 * The viewer's own study goals. `dailyGoals` is keyed by ISO weekday
 * (1 = Monday … 7 = Sunday) and holds only weekdays that have something set.
 */
final class StudyRoomGoalViewModel extends Data
{
    /**
     * @param  array<int, array{minutes: int|null, remindAt: string|null}>  $dailyGoals
     */
    public function __construct(
        public ?int $weeklyGoalMinutes,
        public array $dailyGoals,
        public bool $notifyOnGoalReminder,
        public bool $excludeInPersonClass,
    ) {}

    public static function fromProfile(?StudyRoomProfile $profile): self
    {
        $dailyGoals = [];

        foreach ($profile?->daily_goals ?? [] as $weekday => $goal) {
            $dailyGoals[(int) $weekday] = [
                'minutes' => $goal['minutes'] ?? null,
                'remindAt' => $goal['remindAt'] ?? null,
            ];
        }

        return new self(
            weeklyGoalMinutes: $profile?->weekly_goal_minutes,
            dailyGoals: $dailyGoals,
            notifyOnGoalReminder: $profile?->notify_on_goal_reminder ?? false,
            excludeInPersonClass: $profile?->goal_excludes_in_person_class ?? true,
        );
    }
}
