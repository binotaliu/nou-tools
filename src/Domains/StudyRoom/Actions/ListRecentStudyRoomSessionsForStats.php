<?php

declare(strict_types=1);

namespace NouTools\Domains\StudyRoom\Actions;

use App\Models\StudyRoomSession;
use Illuminate\Support\Facades\Date;
use NouTools\Domains\Schedules\ValueObjects\StudentScheduleCookie;

/**
 * The viewer's own sessions from the last 7 calendar days, newest first,
 * for the Stats modal's bar chart and per-date session log.
 */
final readonly class ListRecentStudyRoomSessionsForStats
{
    private const int DAYS = 7;

    public function __invoke(StudentScheduleCookie $viewer): array
    {
        $timezone = (string) config('app.schedule_timezone');
        $startUtc = Date::now($timezone)->startOfDay()->subDays(self::DAYS - 1)->utc();
        $endUtc = Date::now($timezone)->endOfDay()->utc();

        return StudyRoomSession::query()
            ->where('student_schedule_id', $viewer->id)
            ->whereBetween('ended_at', [$startUtc, $endUtc])
            ->orderByDesc('started_at')
            ->get()
            ->all();
    }
}
