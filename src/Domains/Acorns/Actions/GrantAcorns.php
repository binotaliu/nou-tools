<?php

declare(strict_types=1);

namespace NouTools\Domains\Acorns\Actions;

use App\Enums\AcornTransactionKind;
use App\Models\AcornTransaction;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;
use NouTools\Domains\Acorns\Support\AcornExpiry;

/**
 * Writes one credit. `$localDay` is the Taipei day it counts towards for the
 * caps; it also decides when the credit expires.
 */
final readonly class GrantAcorns
{
    /**
     * @param  array<string, mixed>|null  $meta
     */
    public function __invoke(
        int $scheduleId,
        AcornTransactionKind $kind,
        int $amount,
        CarbonInterface $localDay,
        ?Model $source = null,
        ?array $meta = null,
    ): AcornTransaction {
        $transaction = new AcornTransaction;
        $transaction->student_schedule_id = $scheduleId;
        $transaction->kind = $kind;
        $transaction->amount = $amount;
        $transaction->remaining = $amount;
        $transaction->earned_on = $localDay->toDateString();
        $transaction->expires_at = AcornExpiry::expiresAt($localDay);
        $transaction->meta = $meta;

        if ($source !== null) {
            $transaction->source()->associate($source);
        }

        $transaction->saveOrFail();

        return $transaction;
    }
}
