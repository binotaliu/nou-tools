<?php

declare(strict_types=1);

namespace NouTools\Domains\StudyRoom\Exceptions;

use RuntimeException;

/**
 * Thrown by seat-timer actions (`StartStudyTimer`, `StopStudyTimer`,
 * `StartBreak`) when the viewer doesn't currently hold a seat.
 */
final class NoSeatHeldException extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('請先入座後再開始計時。');
    }
}
