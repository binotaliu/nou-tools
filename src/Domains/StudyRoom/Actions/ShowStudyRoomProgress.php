<?php

declare(strict_types=1);

namespace NouTools\Domains\StudyRoom\Actions;

use App\Models\StudyRoomSession;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\Date;
use NouTools\Domains\Schedules\ValueObjects\StudentScheduleCookie;
use NouTools\Domains\StudyRoom\DataTransferObjects\ShowStudyRoomProgressData;
use NouTools\Domains\StudyRoom\ViewModels\StudyRoomDailyFocusViewModel;
use NouTools\Domains\StudyRoom\ViewModels\StudyRoomProgressViewModel;
use NouTools\Domains\StudyRoom\ViewModels\StudyRoomSessionViewModel;
use NouTools\Domains\StudyRoom\ViewModels\StudyRoomSubjectShareViewModel;
use Spatie\LaravelData\DataCollection;

/**
 * The 專注進度 page: a six-week calendar grid (Monday first) with the dates
 * that have records, and the selected Monday-to-Sunday week's detail. Days
 * are the Taipei date of `ended_at`, like the Stats modal.
 */
final readonly class ShowStudyRoomProgress
{
    private const int GRID_DAYS = 42;

    private const array WEEKDAY_LABELS = ['日', '一', '二', '三', '四', '五', '六'];

    public function __invoke(StudentScheduleCookie $viewer, ShowStudyRoomProgressData $data): StudyRoomProgressViewModel
    {
        $timezone = (string) config('app.schedule_timezone');
        $today = Date::now($timezone)->startOfDay()->toImmutable();

        $weekStart = $data->week !== null
            ? Date::parse($data->week, $timezone)->startOfWeek(CarbonInterface::MONDAY)
            : $today->startOfWeek(CarbonInterface::MONDAY);

        $monthStart = $data->month !== null
            ? Date::parse($data->month.'-01', $timezone)
            : ($data->week !== null ? Date::parse($data->week, $timezone)->startOfMonth() : $today->startOfMonth());

        $gridStart = $monthStart->startOfWeek(CarbonInterface::MONDAY);

        $inScope = fn ($query) => $query
            ->where('student_schedule_id', $viewer->id)
            ->when(! $data->includeInPerson, fn ($query) => $query->excludingInPersonClass());

        $activeDates = StudyRoomSession::query()
            ->tap($inScope)
            ->whereBetween('ended_at', [$gridStart->utc(), $gridStart->addDays(self::GRID_DAYS)->utc()])
            ->pluck('ended_at')
            ->map(fn ($endedAt): string => $endedAt->copy()->timezone($timezone)->toDateString())
            ->unique()
            ->sort()
            ->values()
            ->all();

        $weekSessions = StudyRoomSession::query()
            ->tap($inScope)
            ->whereBetween('ended_at', [$weekStart->utc(), $weekStart->addDays(7)->utc()])
            ->with('subjectCourse')
            ->orderByDesc('started_at')
            ->get()
            ->all();

        $byDate = [];
        foreach ($weekSessions as $session) {
            $byDate[$session->ended_at->copy()->timezone($timezone)->toDateString()][] = $session;
        }

        $days = [];
        for ($offset = 0; $offset < 7; $offset++) {
            $day = $weekStart->addDays($offset);
            $daySessions = $byDate[$day->toDateString()] ?? [];

            $days[] = new StudyRoomDailyFocusViewModel(
                date: $day->toDateString(),
                label: self::WEEKDAY_LABELS[$day->dayOfWeek],
                focusSeconds: array_sum(array_map(fn (StudyRoomSession $session): int => $session->focus_seconds, $daySessions)),
                sessions: StudyRoomSessionViewModel::collect(
                    array_map(fn (StudyRoomSession $session): StudyRoomSessionViewModel => StudyRoomSessionViewModel::fromModel($session), $daySessions),
                    DataCollection::class,
                ),
            );
        }

        return new StudyRoomProgressViewModel(
            today: $today->toDateString(),
            month: $monthStart->format('Y-m'),
            gridStart: $gridStart->toDateString(),
            weekStart: $weekStart->toDateString(),
            includeInPerson: $data->includeInPerson,
            activeDates: $activeDates,
            weekFocusSeconds: array_sum(array_map(fn (StudyRoomDailyFocusViewModel $day): int => $day->focusSeconds, $days)),
            days: StudyRoomDailyFocusViewModel::collect($days, DataCollection::class),
            subjects: StudyRoomSubjectShareViewModel::slicesFromSessions($weekSessions),
        );
    }
}
