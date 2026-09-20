<?php

declare(strict_types=1);

namespace NouTools\Domains\StudyRoom\Exceptions;

use RuntimeException;

/**
 * Thrown by `PauseStudyTimer` when the viewer's seat has no running,
 * un-paused Focus-phase timer — no timer at all, a break, or one that is
 * already paused.
 */
final class CannotPauseTimerException extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('目前沒有進行中的專注計時，無法暫停。');
    }
}
