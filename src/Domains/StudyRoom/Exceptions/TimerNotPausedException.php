<?php

declare(strict_types=1);

namespace NouTools\Domains\StudyRoom\Exceptions;

use RuntimeException;

/**
 * Thrown by `ResumeStudyTimer` when the viewer's timer isn't paused.
 */
final class TimerNotPausedException extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('計時目前沒有暫停，無法繼續。');
    }
}
