<?php

declare(strict_types=1);

namespace App\Enums;

enum AnalyticsConsentState: string
{
    case Granted = 'granted';
    case Denied = 'denied';

    public static function fromCookieValue(?string $value): ?self
    {
        return self::tryFrom((string) $value);
    }
}
