<?php

declare(strict_types=1);

namespace NouTools\Domains\Analytics\DataTransferObjects;

use App\Enums\AnalyticsConsentState;
use Spatie\LaravelData\Data;

final class AnalyticsConsentResolution extends Data
{
    public function __construct(
        public AnalyticsConsentState $state,
        public bool $showBanner,
    ) {}

    /**
     * The visitor already made an explicit choice (cookie present), so
     * nothing to ask them.
     */
    public static function explicit(AnalyticsConsentState $state): self
    {
        return new self($state, showBanner: false);
    }

    /**
     * No explicit choice on record; $state was derived from country and
     * $showBanner tells the caller whether that country requires opt-in.
     */
    public static function implicit(AnalyticsConsentState $state, bool $showBanner): self
    {
        return new self($state, $showBanner);
    }
}
