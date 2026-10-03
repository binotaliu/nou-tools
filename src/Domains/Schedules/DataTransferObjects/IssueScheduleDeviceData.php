<?php

declare(strict_types=1);

namespace NouTools\Domains\Schedules\DataTransferObjects;

use Carbon\CarbonInterface;
use Spatie\LaravelData\Data;

final class IssueScheduleDeviceData extends Data
{
    public function __construct(
        public int $studentScheduleId,
        public string $token,
        public bool $isPersistent,
        public ?string $userAgent,
        public CarbonInterface $issuedAt,
        public CarbonInterface $expiresAt,
    ) {}
}
