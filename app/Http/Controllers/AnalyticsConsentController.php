<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\AnalyticsConsentState;
use Illuminate\Http\JsonResponse;
use NouTools\Domains\Analytics\Actions\BuildAnalyticsConsentCookie;
use NouTools\Domains\Analytics\Actions\SetAnalyticsConsent;
use NouTools\Domains\Analytics\DataTransferObjects\SetAnalyticsConsentData;

final class AnalyticsConsentController extends Controller
{
    public function __invoke(SetAnalyticsConsentData $input, SetAnalyticsConsent $setAnalyticsConsent, BuildAnalyticsConsentCookie $buildCookie): JsonResponse
    {
        $state = $setAnalyticsConsent($input);

        return response()
            ->json(['ok' => true, 'granted' => $state === AnalyticsConsentState::Granted])
            ->withCookie($buildCookie($state));
    }
}
