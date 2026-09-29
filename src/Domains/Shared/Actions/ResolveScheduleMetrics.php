<?php

declare(strict_types=1);

namespace NouTools\Domains\Shared\Actions;

use App\Models\StudentSchedule;
use App\Models\StudyRoomProfile;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\Date;

final readonly class ResolveScheduleMetrics
{
    /**
     * Adoption numbers for the admin dashboard. Days are bucketed in the
     * scheduler's timezone (Taipei) so "today" matches what admins see.
     *
     * @return array{
     *     total: int,
     *     newThisWeek: int,
     *     reminderRate: float,
     *     calendarSyncRate: float,
     *     profiles: int,
     *     dailyNew: array<string, int>,
     * }
     */
    public function __invoke(int $days = 30): array
    {
        $timezone = (string) config('app.schedule_timezone');
        $today = Date::now($timezone)->startOfDay();
        $windowStart = $today->subDays($days - 1);

        $total = StudentSchedule::query()->count();

        return [
            'total' => $total,
            'newThisWeek' => StudentSchedule::query()
                ->where('created_at', '>=', $today->subDays(6)->utc())
                ->count(),
            'reminderRate' => $this->rate(StudentSchedule::query()->where('notify_on_class_start', true)->count(), $total),
            'calendarSyncRate' => $this->rate(StudentSchedule::query()->whereNotNull('last_calendar_sync_at')->count(), $total),
            'profiles' => StudyRoomProfile::query()->count(),
            'dailyNew' => $this->dailyNew($windowStart, $days, $timezone),
        ];
    }

    /**
     * @return array<string, int>
     */
    private function dailyNew(CarbonInterface $windowStart, int $days, string $timezone): array
    {
        $buckets = [];

        for ($offset = 0; $offset < $days; $offset++) {
            $buckets[$windowStart->addDays($offset)->toDateString()] = 0;
        }

        StudentSchedule::query()
            ->where('created_at', '>=', $windowStart->utc())
            ->pluck('created_at')
            ->each(function (CarbonInterface $createdAt) use (&$buckets, $timezone): void {
                $day = $createdAt->setTimezone($timezone)->toDateString();

                if (isset($buckets[$day])) {
                    $buckets[$day]++;
                }
            });

        return $buckets;
    }

    private function rate(int $part, int $total): float
    {
        return $total === 0 ? 0.0 : round($part / $total * 100, 1);
    }
}
