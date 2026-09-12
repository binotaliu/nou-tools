<?php

declare(strict_types=1);

namespace NouTools\Domains\StudyRoom\Exceptions;

use RuntimeException;

/**
 * Thrown by `TakeSeat` when the viewer already holds a different seat —
 * seat switching is disallowed, so the viewer must leave their current
 * seat before claiming another one.
 */
final class AlreadySeatedException extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('請先離開目前的座位，才能選擇其他座位。');
    }
}
