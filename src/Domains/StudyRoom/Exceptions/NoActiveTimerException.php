<?php

declare(strict_types=1);

namespace NouTools\Domains\StudyRoom\Exceptions;

use RuntimeException;

/**
 * Thrown by `ChangeStudyActivity` when the viewer's seat has no running
 * Focus-phase timer to change the activity of — either no timer is running
 * at all, or the seat is currently on a break.
 */
final class NoActiveTimerException extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('目前沒有進行中的專注計時，無法變更活動。');
    }
}
