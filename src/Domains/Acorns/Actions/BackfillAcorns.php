<?php

declare(strict_types=1);

namespace NouTools\Domains\Acorns\Actions;

use App\Enums\AcornTransactionKind;
use App\Models\AcornTransaction;
use App\Models\LearningProgress;
use App\Models\LearningProgressAcornClaim;
use App\Models\StudyRoomSession;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use NouTools\Domains\Acorns\Support\AcornExpiry;
use NouTools\Domains\Acorns\Support\LearningProgressItems;

/**
 * One-off credit for people who were studying before acorns existed.
 *
 * Study sessions of every day whose credits would still be alive are replayed
 * through the same caps (older days pay nothing, see `AwardStudyAcorns`).
 * Learning-progress ticks carry no timestamp, so they are claimed once and
 * paid as a single Welcome credit dated today, capped at `welcome_cap`.
 * Both halves are idempotent.
 */
final readonly class BackfillAcorns
{
    public function __construct(
        private AwardStudyAcorns $awardStudyAcorns,
        private GrantAcorns $grantAcorns,
    ) {}

    /**
     * @return array{schedules: int, study: int, welcome: int}
     */
    public function __invoke(): array
    {
        $today = AcornExpiry::localDay(Date::now());
        $oldest = $today->copy()->subDays((int) config('cafe.acorns.expires_after_days'));

        $scheduleIds = StudyRoomSession::query()
            ->where('ended_at', '>=', $oldest->copy()->utc())
            ->distinct()
            ->pluck('student_schedule_id');

        $study = 0;

        foreach ($scheduleIds as $scheduleId) {
            for ($day = $oldest->copy(); $day->lessThanOrEqualTo($today); $day = $day->copy()->addDay()) {
                $study += ($this->awardStudyAcorns)((int) $scheduleId, $day);
            }
        }

        $welcome = 0;
        $welcomed = [];

        LearningProgress::query()->orderBy('id')->each(function (LearningProgress $row) use (&$welcome, &$welcomed): void {
            $paid = DB::transaction(function () use ($row): int {
                $scheduleId = $row->student_schedule_id;
                $items = LearningProgressItems::ticked($row->progress ?? [], $row->homework ?? []);

                if ($items === []) {
                    return 0;
                }

                $claimed = LearningProgressAcornClaim::query()
                    ->where('student_schedule_id', $scheduleId)
                    ->where('term', $row->term)
                    ->pluck('item_key')
                    ->all();

                $fresh = array_diff_key($items, array_flip($claimed));

                if ($fresh === []) {
                    return 0;
                }

                $alreadyWelcomed = AcornTransaction::query()
                    ->where('student_schedule_id', $scheduleId)
                    ->where('kind', AcornTransactionKind::Welcome)
                    ->exists();

                $now = Date::now();
                LearningProgressAcornClaim::query()->insert(array_map(
                    fn (string $key): array => [
                        'student_schedule_id' => $scheduleId,
                        'term' => $row->term,
                        'item_key' => $key,
                        'acorn_transaction_id' => null,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ],
                    array_keys($fresh),
                ));

                return $alreadyWelcomed ? 0 : array_sum($fresh);
            });

            if ($paid > 0) {
                $welcomed[$row->student_schedule_id] = ($welcomed[$row->student_schedule_id] ?? 0) + $paid;
            }
        });

        foreach ($welcomed as $scheduleId => $value) {
            $amount = min($value, (int) config('cafe.acorns.welcome_cap'));
            ($this->grantAcorns)($scheduleId, AcornTransactionKind::Welcome, $amount, $today);
            $welcome += $amount;
        }

        return ['schedules' => $scheduleIds->count(), 'study' => $study, 'welcome' => $welcome];
    }
}
