<?php

declare(strict_types=1);

namespace NouTools\Domains\Analytics\Actions;

use App\Enums\AnalyticsConsentState;
use NouTools\Domains\Analytics\DataTransferObjects\SetAnalyticsConsentData;

final readonly class SetAnalyticsConsent
{
    public function __invoke(SetAnalyticsConsentData $data): AnalyticsConsentState
    {
        return $data->granted ? AnalyticsConsentState::Granted : AnalyticsConsentState::Denied;
    }
}
