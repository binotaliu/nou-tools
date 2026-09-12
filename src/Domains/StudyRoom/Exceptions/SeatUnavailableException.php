<?php

declare(strict_types=1);

namespace NouTools\Domains\StudyRoom\Exceptions;

use RuntimeException;

/**
 * Thrown by `TakeSeat` when the target seat is already occupied — either the
 * conditional claim UPDATE affected zero rows, or two concurrent claims hit
 * the `unique(student_schedule_id)` constraint at the same instant.
 */
final class SeatUnavailableException extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('這個位子已經有人坐了。');
    }
}
