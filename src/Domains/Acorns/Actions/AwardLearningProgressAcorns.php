<?php

declare(strict_types=1);

namespace NouTools\Domains\Acorns\Actions;

use App\Enums\AcornTransactionKind;
use App\Models\AcornTransaction;
use App\Models\LearningProgressAcornClaim;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use NouTools\Domains\Acorns\Support\AcornExpiry;
use NouTools\Domains\Acorns\Support\LearningProgressItems;

/**
 * Pays for learning-progress items that just became ticked.
 *
 * Every newly ticked item gets a claim row, paid or not, so an item pays once
 * ever: un-ticking and ticking it again, or ticking it after the daily cap was
 * hit, earns nothing more.
 */
final readonly class AwardLearningProgressAcorns
{
    public function __construct(private GrantAcorns $grantAcorns) {}

    /**
     * @param  array{progress: array<array-key, mixed>, homework: array<array-key, mixed>}  $before
     * @param  array{progress: array<array-key, mixed>, homework: array<array-key, mixed>}  $after
     * @return array{awarded: int, capped: bool}
     */
    public function __invoke(int $scheduleId, string $term, array $before, array $after): array
    {
        $newlyTicked = array_diff_key(
            LearningProgressItems::ticked($after['progress'], $after['homework']),
            LearningProgressItems::ticked($before['progress'], $before['homework']),
        );

        if ($newlyTicked === []) {
            return ['awarded' => 0, 'capped' => false];
        }

        return DB::transaction(function () use ($scheduleId, $term, $newlyTicked): array {
            DB::table('student_schedules')->where('id', $scheduleId)->lockForUpdate()->value('id');

            $claimed = LearningProgressAcornClaim::query()
                ->where('student_schedule_id', $scheduleId)
                ->where('term', $term)
                ->whereIn('item_key', array_keys($newlyTicked))
                ->pluck('item_key')
                ->all();

            $fresh = array_diff_key($newlyTicked, array_flip($claimed));

            if ($fresh === []) {
                return ['awarded' => 0, 'capped' => false];
            }

            $localDay = AcornExpiry::localDay(Date::now());

            $paidToday = (int) AcornTransaction::query()
                ->where('student_schedule_id', $scheduleId)
                ->where('kind', AcornTransactionKind::LearningProgress)
                ->where('amount', '>', 0)
                ->where('earned_on', $localDay->toDateString())
                ->sum('amount');

            $value = array_sum($fresh);
            $pay = max(0, min($value, (int) config('cafe.acorns.learning_progress.daily_cap') - $paidToday));

            $transaction = $pay > 0
                ? ($this->grantAcorns)($scheduleId, AcornTransactionKind::LearningProgress, $pay, $localDay, null, ['term' => $term])
                : null;

            $now = Date::now();
            LearningProgressAcornClaim::query()->insert(array_map(
                fn (string $key): array => [
                    'student_schedule_id' => $scheduleId,
                    'term' => $term,
                    'item_key' => $key,
                    'acorn_transaction_id' => $transaction?->id,
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
                array_keys($fresh),
            ));

            return ['awarded' => $pay, 'capped' => $pay < $value];
        });
    }
}
