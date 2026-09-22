<?php

declare(strict_types=1);

namespace NouTools\Domains\Analytics\Actions;

use App\Enums\AnalyticsConsentState;
use Illuminate\Http\Request;
use NouTools\Domains\Analytics\DataTransferObjects\AnalyticsConsentResolution;
use NouTools\Domains\Shared\Geo\Actions\ResolveVisitorCountry;

/**
 * Single source of truth for whether GA analytics should be granted and
 * whether the consent banner needs to ask: an explicit past choice (cookie)
 * always wins; otherwise Taiwan defaults to granted/no-banner (opt-out) and
 * everywhere else — including when the country can't be determined, e.g.
 * local dev which isn't behind Cloudflare — defaults to denied/banner shown
 * (opt-in).
 */
final readonly class ResolveAnalyticsConsent
{
    public function __construct(
        private ReadAnalyticsConsentCookie $readCookie,
        private ResolveVisitorCountry $resolveCountry,
    ) {}

    public function __invoke(Request $request): AnalyticsConsentResolution
    {
        $cookieState = ($this->readCookie)($request);

        if ($cookieState !== null) {
            return AnalyticsConsentResolution::explicit($cookieState);
        }

        $country = ($this->resolveCountry)($request);

        return $country === 'TW'
            ? AnalyticsConsentResolution::implicit(AnalyticsConsentState::Granted, showBanner: false)
            : AnalyticsConsentResolution::implicit(AnalyticsConsentState::Denied, showBanner: true);
    }
}
