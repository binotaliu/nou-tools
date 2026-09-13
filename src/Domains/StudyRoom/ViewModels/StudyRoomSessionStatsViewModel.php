<?php

declare(strict_types=1);

namespace NouTools\Domains\StudyRoom\ViewModels;

use App\Models\StudyRoomSession;
use Illuminate\Support\Facades\Date;
use Spatie\LaravelData\Attributes\DataCollectionOf;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\DataCollection;

final class StudyRoomSessionStatsViewModel extends Data
{
    private const int DAYS = 7;

    private const array WEEKDAY_LABELS = ['日', '一', '二', '三', '四', '五', '六'];

    public function __construct(
        #[DataCollectionOf(StudyRoomDailyFocusViewModel::class)]
        public DataCollection $days,
    ) {}

    /**
     * Buckets the viewer's sessions into the last 7 calendar days (oldest
     * first, today last) in `$timezone`. Days with no sessions still get an
     * entry so the bar chart always renders 7 consistent bars.
     *
     * @param  array<int, StudyRoomSession>  $sessions
     */
    public static function fromSessions(array $sessions, string $timezone): self
    {
        $today = Date::now($timezone)->startOfDay();

        $byDate = [];
        foreach ($sessions as $session) {
            $date = $session->ended_at->copy()->timezone($timezone)->toDateString();
            $byDate[$date][] = $session;
        }

        $days = [];
        for ($offset = self::DAYS - 1; $offset >= 0; $offset--) {
            $day = $today->copy()->subDays($offset);
            $date = $day->toDateString();
            $daySessions = $byDate[$date] ?? [];

            $days[] = new StudyRoomDailyFocusViewModel(
                date: $date,
                label: $offset === 0 ? '今天' : self::WEEKDAY_LABELS[$day->dayOfWeek],
                focusSeconds: array_sum(array_map(fn (StudyRoomSession $session): int => $session->focus_seconds, $daySessions)),
                sessions: StudyRoomSessionViewModel::collect(
                    array_map(fn (StudyRoomSession $session): StudyRoomSessionViewModel => StudyRoomSessionViewModel::fromModel($session), $daySessions),
                    DataCollection::class,
                ),
            );
        }

        return new self(days: StudyRoomDailyFocusViewModel::collect($days, DataCollection::class));
    }
}
