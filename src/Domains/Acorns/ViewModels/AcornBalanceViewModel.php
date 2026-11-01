<?php

declare(strict_types=1);

namespace NouTools\Domains\Acorns\ViewModels;

use Spatie\LaravelData\Data;

final class AcornBalanceViewModel extends Data
{
    /**
     * @param  array<int, AcornTransactionViewModel>  $recent
     */
    public function __construct(
        public int $balance,
        public int $studyToday,
        public int $studyDailyCap,
        public int $studyThisWeek,
        public int $studyWeeklyCap,
        public int $learningToday,
        public int $learningDailyCap,
        public ?int $nextExpiryAmount,
        public ?string $nextExpiresAt,
        public array $recent = [],
    ) {}
}
