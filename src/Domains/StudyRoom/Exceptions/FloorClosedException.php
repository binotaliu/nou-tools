<?php

declare(strict_types=1);

namespace NouTools\Domains\StudyRoom\Exceptions;

use RuntimeException;

/**
 * Thrown by `TakeSeat` when the target seat's floor is no longer among the
 * currently open floors (re-checked inside the claim transaction, since a
 * stale client-side read can't be trusted).
 */
final class FloorClosedException extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('這個樓層目前尚未開放。');
    }
}
