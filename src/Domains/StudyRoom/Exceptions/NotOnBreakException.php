<?php

declare(strict_types=1);

namespace NouTools\Domains\StudyRoom\Exceptions;

use RuntimeException;

/**
 * Thrown by `StartNextRound` when the viewer's seat isn't in a pomodoro
 * break — the next round only ever follows a break, never a focus timer
 * (finished or not) or no timer at all.
 */
final class NotOnBreakException extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('目前不在休息中，無法開始下一輪。');
    }
}
