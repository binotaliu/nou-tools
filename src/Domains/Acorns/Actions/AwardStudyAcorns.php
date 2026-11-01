<?php

declare(strict_types=1);

namespace NouTools\Domains\Acorns\Actions;

use App\Enums\AcornTransactionKind;
use App\Models\AcornTransaction;
use App\Models\StudyRoomSession;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;
use NouTools\Domains\Acorns\Support\AcornExpiry;

/**
 * Brings a Taipei day's study acorns up to what its sessions are worth.
 *
 * It recomputes the whole day instead of counting one session, so fractional
 * pomodoro segments need no carry-over state and running it twice (or from
 * the backfill) never pays twice: the target is
 * `min(daily cap, floor(focus seconds / seconds per acorn), weekly cap left)`
 * and only the difference to what the day already paid is credited. 面授
 * sessions do not count. A day whose credits would already have expired pays
 * nothing, which is what lets the backfill replay a long history safely.
 */
final readonly class AwardStudyAcorns
{
    public function __construct(private GrantAcorns $grantAcorns) {}

    public function __invoke(int $scheduleId, CarbonInterface $moment): int
    {
        $localDay = AcornExpiry::localDay($moment);

        if (AcornExpiry::expiresAt($localDay)->isPast()) {
            return 0;
        }

        return DB::transaction(function () use ($scheduleId, $localDay): int {
            DB::table('student_schedules')->where('id', $scheduleId)->lockForUpdate()->value('id');

            $dayStart = $localDay->copy()->utc();
            $dayEnd = $localDay->copy()->addDay()->utc();

            $seconds = (int) StudyRoomSession::query()
                ->excludingInPersonClass()
                ->where('student_schedule_id', $scheduleId)
                ->where('ended_at', '>=', $dayStart)
                ->where('ended_at', '<', $dayEnd)
                ->sum('focus_seconds');

            $target = min(
                (int) config('cafe.acorns.study.daily_cap'),
                intdiv($seconds, (int) config('cafe.acorns.study.seconds_per_acorn')),
            );

            $weekStart = $localDay->copy()->startOfWeek(CarbonInterface::MONDAY);
            $weekEnd = $weekStart->copy()->addWeek();

            $awardedByDay = AcornTransaction::query()
                ->where('student_schedule_id', $scheduleId)
                ->where('kind', AcornTransactionKind::StudyFocus)
                ->where('amount', '>', 0)
                ->where('earned_on', '>=', $weekStart->toDateString())
                ->where('earned_on', '<', $weekEnd->toDateString())
                ->selectRaw('earned_on, sum(amount) as total')
                ->groupBy('earned_on')
                ->pluck('total', 'earned_on');

            $awardedToday = 0;
            $awardedOtherDays = 0;

            foreach ($awardedByDay as $date => $total) {
                if (substr((string) $date, 0, 10) === $localDay->toDateString()) {
                    $awardedToday += (int) $total;
                } else {
                    $awardedOtherDays += (int) $total;
                }
            }

            $weeklyRoom = max(0, (int) config('cafe.acorns.study.weekly_cap') - $awardedOtherDays);
            $delta = min($target, $weeklyRoom) - $awardedToday;

            if ($delta <= 0) {
                return 0;
            }

            ($this->grantAcorns)($scheduleId, AcornTransactionKind::StudyFocus, $delta, $localDay);

            return $delta;
        });
    }
}
