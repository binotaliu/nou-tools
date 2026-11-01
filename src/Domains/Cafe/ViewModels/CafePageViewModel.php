<?php

declare(strict_types=1);

namespace NouTools\Domains\Cafe\ViewModels;

use Spatie\LaravelData\Data;

final class CafePageViewModel extends Data
{
    public function __construct(
        public bool $hasSchedule,
        public bool $hasNickname,
        public ?string $nickname,
        public ?string $emoji,
        public int $heartbeatIntervalSeconds,
        public int $connectTimeoutSeconds,
        public int $notePrice,
        public int $noteMaxLength,
        public int $noteLifetimeDays,
    ) {}
}
