<?php

declare(strict_types=1);

namespace NouTools\Domains\StudyRoom\Exceptions;

use RuntimeException;

/**
 * Thrown by `StartBreak` when the viewer's focus timer hasn't finished yet
 * — the break is manual (the student presses 開始休息 themselves), so it
 * can never be started early.
 */
final class TimerNotFinishedException extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('計時尚未結束，無法開始休息。');
    }
}
