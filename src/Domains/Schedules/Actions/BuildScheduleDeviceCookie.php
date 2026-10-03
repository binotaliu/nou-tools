<?php

declare(strict_types=1);

namespace NouTools\Domains\Schedules\Actions;

use Symfony\Component\HttpFoundation\Cookie;

final class BuildScheduleDeviceCookie
{
    public const NAME = 'schedule_device';

    /** The longest lifetime browsers honour for a cookie. */
    public const PERSISTENT_DAYS = 400;

    /** A device not marked "remember" lives for the browser session, and is dropped server-side after this. */
    public const SESSION_HOURS = 12;

    public function __invoke(string $token, bool $isPersistent): Cookie
    {
        return cookie(
            self::NAME,
            $token,
            $isPersistent ? self::PERSISTENT_DAYS * 24 * 60 : 0,
        );
    }
}
