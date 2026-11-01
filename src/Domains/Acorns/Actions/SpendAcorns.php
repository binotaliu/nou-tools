<?php

declare(strict_types=1);

namespace NouTools\Domains\Acorns\Actions;

use App\Enums\AcornTransactionKind;
use App\Models\AcornTransaction;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use NouTools\Domains\Acorns\Exceptions\InsufficientAcornsException;
use NouTools\Domains\Acorns\Support\AcornExpiry;

/**
 * Spends from the credits that expire soonest. The schedule row is locked so
 * two purchases at once cannot spend the same acorns.
 */
final readonly class SpendAcorns
{
    /**
     * @param  array<string, mixed>|null  $meta
     *
     * @throws InsufficientAcornsException
     */
    public function __invoke(
        int $scheduleId,
        int $amount,
        AcornTransactionKind $kind,
        ?Model $source = null,
        ?array $meta = null,
    ): AcornTransaction {
        return DB::transaction(function () use ($scheduleId, $amount, $kind, $source, $meta): AcornTransaction {
            DB::table('student_schedules')->where('id', $scheduleId)->lockForUpdate()->value('id');

            $credits = AcornTransaction::query()
                ->where('student_schedule_id', $scheduleId)
                ->spendable()
                ->orderBy('expires_at')
                ->orderBy('id')
                ->get();

            if ($credits->sum('remaining') < $amount) {
                throw new InsufficientAcornsException;
            }

            $left = $amount;

            foreach ($credits as $credit) {
                if ($left === 0) {
                    break;
                }

                $take = min($left, (int) $credit->remaining);
                $credit->remaining -= $take;
                $credit->saveOrFail();
                $left -= $take;
            }

            $spend = new AcornTransaction;
            $spend->student_schedule_id = $scheduleId;
            $spend->kind = $kind;
            $spend->amount = -$amount;
            $spend->earned_on = AcornExpiry::localDay(Date::now())->toDateString();
            $spend->meta = $meta;

            if ($source !== null) {
                $spend->source()->associate($source);
            }

            $spend->saveOrFail();

            return $spend;
        });
    }
}
