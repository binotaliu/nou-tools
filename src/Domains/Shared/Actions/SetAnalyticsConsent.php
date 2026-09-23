<?php

declare(strict_types=1);

namespace NouTools\Domains\Shared\Actions;

use App\Enums\AnalyticsConsentState;
use NouTools\Domains\Shared\DataTransferObjects\SetAnalyticsConsentData;

final readonly class SetAnalyticsConsent
{
    public function __invoke(SetAnalyticsConsentData $data): AnalyticsConsentState
    {
        return $data->granted ? AnalyticsConsentState::Granted : AnalyticsConsentState::Denied;
    }
}
