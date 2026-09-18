<?php

declare(strict_types=1);

namespace NouTools\Domains\Newsletter\DataTransferObjects;

use Carbon\CarbonImmutable;
use Spatie\LaravelData\Data;

/**
 * The dates that belong to one issue, all derived from the Monday it is
 * published on (P): it is edited during P-7..P-1, curates announcements
 * from P-14..P-1, and highlights school events in P..P+13.
 */
final class NewsletterIssueScheduleDTO extends Data
{
    public function __construct(
        public string $issueKey,
        public CarbonImmutable $publishesOn,
        public CarbonImmutable $editingStartsOn,
        public CarbonImmutable $coversFrom,
        public CarbonImmutable $coversTo,
        public CarbonImmutable $highlightsFrom,
        public CarbonImmutable $highlightsTo,
    ) {}
}
