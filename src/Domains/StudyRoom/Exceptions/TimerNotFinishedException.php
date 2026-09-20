<?php

declare(strict_types=1);

namespace NouTools\Domains\StudyRoom\Exceptions;

use RuntimeException;

/**
 * Thrown by `StartBreak` when the viewer has no running focus timer to
 * end — a non-pomodoro timer that hasn't run out yet, a paused one, or
 * one that's already on its break.
 */
final class TimerNotFinishedException extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('計時尚未結束，無法開始休息。');
    }
}
