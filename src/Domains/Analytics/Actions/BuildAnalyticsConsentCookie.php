<?php

declare(strict_types=1);

namespace NouTools\Domains\Analytics\Actions;

use App\Enums\AnalyticsConsentState;
use Symfony\Component\HttpFoundation\Cookie;

final class BuildAnalyticsConsentCookie
{
    private const int LIFETIME_DAYS = 180;

    public function __invoke(AnalyticsConsentState $state): Cookie
    {
        return cookie('analytics_consent', $state->value, 60 * 24 * self::LIFETIME_DAYS);
    }
}
