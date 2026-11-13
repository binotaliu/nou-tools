<?php

declare(strict_types=1);

namespace NouTools\Domains\Acorns\Actions;

use App\Enums\AcornTransactionKind;
use App\Models\AcornTransaction;
use App\Models\User;
use Illuminate\Support\Facades\Date;
use NouTools\Domains\Acorns\Support\AcornExpiry;

/**
 * An admin hands acorns to one schedule. It is an ordinary credit (it expires
 * like any other, counted from today in Taipei) tagged with who gave it and why.
 */
final readonly class GrantAcornsAsAdmin
{
    public function __construct(private GrantAcorns $grantAcorns) {}

    public function __invoke(int $scheduleId, int $amount, ?string $reason, User $admin): AcornTransaction
    {
        return ($this->grantAcorns)(
            $scheduleId,
            AcornTransactionKind::AdminAdjust,
            $amount,
            AcornExpiry::localDay(Date::now()),
            meta: array_filter([
                'adminId' => $admin->id,
                'adminName' => $admin->name,
                'reason' => $reason,
            ], fn (mixed $value): bool => $value !== null && $value !== ''),
        );
    }
}
