<?php

declare(strict_types=1);

namespace NouTools\Domains\Analytics\Actions;

use App\Enums\AnalyticsConsentState;
use Illuminate\Http\Request;

final class ReadAnalyticsConsentCookie
{
    public function __invoke(Request $request): ?AnalyticsConsentState
    {
        return AnalyticsConsentState::fromCookieValue($request->cookie('analytics_consent'));
    }
}
