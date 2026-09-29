<?php

declare(strict_types=1);

namespace NouTools\Domains\Shared\Actions;

use App\Models\StudyRoomSeat;
use App\Models\StudyRoomSession;
use Illuminate\Support\Facades\Date;

final readonly class ResolveStudyRoomMetrics
{
    /**
     * Live occupancy plus study-session history for the admin dashboard.
     * Days are bucketed in Taipei time; focus hours are keyed by Y-m-d.
     *
     * @return array{
     *     occupied: int,
     *     capacity: int,
     *     focusHoursThisWeek: float,
     *     sessionsToday: int,
     *     completionRate: float,
     *     dailyFocusHours: array<string, float>,
     * }
     */
    public function __invoke(int $days = 14): array
    {
        $timezone = (string) config('app.schedule_timezone');
        $today = Date::now($timezone)->startOfDay();
        $windowStart = $today->subDays($days - 1);
        $weekStart = $today->subDays(6);

        $buckets = [];

        for ($offset = 0; $offset < $days; $offset++) {
            $buckets[$windowStart->addDays($offset)->toDateString()] = 0;
        }

        $weekFocusSeconds = 0;
        $weekSessions = 0;
        $weekCompleted = 0;
        $sessionsToday = 0;

        StudyRoomSession::query()
            ->where('started_at', '>=', $windowStart->utc())
            ->get(['started_at', 'focus_seconds', 'was_completed'])
            ->each(function (StudyRoomSession $session) use (&$buckets, &$weekFocusSeconds, &$weekSessions, &$weekCompleted, &$sessionsToday, $timezone, $today, $weekStart): void {
                $startedAt = $session->started_at->setTimezone($timezone);
                $day = $startedAt->toDateString();

                $buckets[$day] = ($buckets[$day] ?? 0) + $session->focus_seconds;

                if ($startedAt >= $weekStart) {
                    $weekFocusSeconds += $session->focus_seconds;
                    $weekSessions++;
                    $weekCompleted += $session->was_completed ? 1 : 0;
                }

                if ($startedAt >= $today) {
                    $sessionsToday++;
                }
            });

        return [
            'occupied' => StudyRoomSeat::query()->whereNotNull('student_schedule_id')->count(),
            'capacity' => StudyRoomSeat::query()->count(),
            'focusHoursThisWeek' => round($weekFocusSeconds / 3600, 1),
            'sessionsToday' => $sessionsToday,
            'completionRate' => $weekSessions === 0 ? 0.0 : round($weekCompleted / $weekSessions * 100, 1),
            'dailyFocusHours' => array_map(fn (int $seconds): float => round($seconds / 3600, 1), $buckets),
        ];
    }
}
