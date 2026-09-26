<?php

declare(strict_types=1);

namespace NouTools\Domains\Schedules\ViewModels;

use Spatie\LaravelData\Data;

/**
 * What the 備份課表連結 dialog needs. The URL is the schedule's edit link, so
 * it is a credential: the page presents it as something to keep, not to share.
 */
final class ScheduleBackupViewModel extends Data
{
    public function __construct(
        public string $name,
        public string $url,
        public string $qrCodeSvg,
    ) {}
}
