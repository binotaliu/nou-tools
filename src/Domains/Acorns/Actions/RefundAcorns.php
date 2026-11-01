<?php

declare(strict_types=1);

namespace NouTools\Domains\Acorns\Actions;

use App\Enums\AcornTransactionKind;
use App\Models\AcornTransaction;
use Illuminate\Support\Facades\Date;
use NouTools\Domains\Acorns\Support\AcornExpiry;

/**
 * Gives a spend back as a fresh credit with a new expiry, so a refund is never
 * worth less than the acorns that were taken.
 */
final readonly class RefundAcorns
{
    public function __construct(private GrantAcorns $grantAcorns) {}

    public function __invoke(AcornTransaction $spend): AcornTransaction
    {
        return ($this->grantAcorns)(
            $spend->student_schedule_id,
            AcornTransactionKind::Refund,
            abs($spend->amount),
            AcornExpiry::localDay(Date::now()),
            null,
            ['refundOf' => $spend->id],
        );
    }
}
