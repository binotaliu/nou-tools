<?php

declare(strict_types=1);

namespace NouTools\Domains\Acorns\Support;

use Carbon\CarbonInterface;

/**
 * Day boundaries and expiry are all in Taipei time: a credit earned on a
 * Taipei day lives until the end of that day plus `expires_after_days`.
 */
final class AcornExpiry
{
    public static function localDay(CarbonInterface $moment): CarbonInterface
    {
        return $moment->copy()->setTimezone((string) config('app.schedule_timezone'))->startOfDay();
    }

    public static function expiresAt(CarbonInterface $localDay): CarbonInterface
    {
        return $localDay->copy()
            ->addDay()
            ->addDays((int) config('cafe.acorns.expires_after_days'))
            ->utc();
    }
}
