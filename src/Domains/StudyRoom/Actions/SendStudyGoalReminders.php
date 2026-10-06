<?php

declare(strict_types=1);

namespace NouTools\Domains\StudyRoom\Actions;

use App\Enums\StudyActivityVerb;
use App\Models\StudyRoomProfile;
use App\Models\StudyRoomSession;
use App\Notifications\StudyGoalReminder;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\Date;
use Throwable;

/**
 * Pushes the study goal reminder at each student's per-weekday time, unless
 * the goal it is about is already met. Minute-by-minute sweep, like the
 * class reminders: it only reads current state, so editing a goal needs
 * nothing to be cancelled.
 */
final readonly class SendStudyGoalReminders
{
    /**
     * How long after the chosen time a reminder is still worth sending, so a
     * missed minute or a short scheduler outage doesn't drop it.
     */
    private const GRACE_MINUTES = 10;

    public function __invoke(): int
    {
        $now = Date::now(config('app.schedule_timezone'));
        $weekday = $now->isoWeekday();

        $profiles = StudyRoomProfile::query()
            ->where('notify_on_goal_reminder', true)
            ->where(fn ($query) => $query
                ->whereNull('goal_reminder_sent_on')
                ->orWhere('goal_reminder_sent_on', '<', $now->toDateString()))
            ->whereHas('schedule.pushSubscriptions')
            ->with('schedule')
            ->get();

        $sentCount = 0;

        foreach ($profiles as $profile) {
            $remindAt = $profile->daily_goals[$weekday]['remindAt'] ?? null;

            if ($remindAt === null || ! $this->isDue($now, $remindAt)) {
                continue;
            }

            $remainingToday = $this->remainingMinutes($profile->daily_goals[$weekday]['minutes'] ?? null, $this->focusSeconds($profile, $now->copy()->startOfDay(), $now));
            $remainingWeek = $this->remainingMinutes($profile->weekly_goal_minutes, $this->focusSeconds($profile, $now->copy()->startOfWeek(CarbonInterface::MONDAY), $now));

            // A met goal is the one the reminder would be about, so stay quiet;
            // with no goal at all the reminder is a plain nudge.
            $hasGoal = $remainingToday !== null || $remainingWeek !== null;
            $isMet = $remainingToday === 0 || ($remainingToday === null && $remainingWeek === 0);

            if ($hasGoal && $isMet) {
                continue;
            }

            if (! $this->claim($profile, $now)) {
                continue;
            }

            try {
                $profile->schedule->notify(new StudyGoalReminder($remainingToday, $remainingWeek));
            } catch (Throwable $exception) {
                report($exception);

                continue;
            }

            $sentCount++;
        }

        return $sentCount;
    }

    private function isDue(CarbonInterface $now, string $remindAt): bool
    {
        $due = $now->copy()->setTimeFromTimeString($remindAt);

        return $now->greaterThanOrEqualTo($due) && $now->lessThanOrEqualTo($due->copy()->addMinutes(self::GRACE_MINUTES));
    }

    private function focusSeconds(StudyRoomProfile $profile, CarbonInterface $from, CarbonInterface $to): int
    {
        return (int) StudyRoomSession::query()
            ->where('student_schedule_id', $profile->student_schedule_id)
            ->when($profile->goal_excludes_in_person_class, fn ($query) => $query->where(fn ($query) => $query
                ->whereNull('activity_verb')
                ->orWhere('activity_verb', '!=', StudyActivityVerb::InPersonClass)))
            ->whereBetween('ended_at', [$from->copy()->utc(), $to->copy()->utc()])
            ->sum('focus_seconds');
    }

    private function remainingMinutes(?int $goalMinutes, int $focusSeconds): ?int
    {
        if ($goalMinutes === null) {
            return null;
        }

        return max(0, $goalMinutes - intdiv($focusSeconds, 60));
    }

    /**
     * Stamp today's date before sending with a conditional UPDATE, so two
     * overlapping sweeps cannot both notify the same student.
     */
    private function claim(StudyRoomProfile $profile, CarbonInterface $now): bool
    {
        return StudyRoomProfile::query()
            ->whereKey($profile->getKey())
            ->where(fn ($query) => $query
                ->whereNull('goal_reminder_sent_on')
                ->orWhere('goal_reminder_sent_on', '<', $now->toDateString()))
            ->update(['goal_reminder_sent_on' => $now->toDateString()]) === 1;
    }
}
