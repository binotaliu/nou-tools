<?php

declare(strict_types=1);

namespace NouTools\Domains\Acorns\ViewModels;

use App\Models\AcornTransaction;
use Spatie\LaravelData\Data;

final class AcornTransactionViewModel extends Data
{
    public function __construct(
        public int $id,
        public string $kind,
        public string $label,
        public int $amount,
        public string $earnedOn,
        public ?string $expiresAt,
    ) {}

    public static function fromModel(AcornTransaction $transaction): self
    {
        return new self(
            id: $transaction->id,
            kind: $transaction->kind->value,
            label: $transaction->kind->label(),
            amount: $transaction->amount,
            earnedOn: substr((string) $transaction->earned_on, 0, 10),
            expiresAt: $transaction->expires_at?->toIso8601String(),
        );
    }
}
