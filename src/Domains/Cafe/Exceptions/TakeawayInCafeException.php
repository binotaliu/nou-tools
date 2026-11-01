<?php

declare(strict_types=1);

namespace NouTools\Domains\Cafe\Exceptions;

use RuntimeException;

final class TakeawayInCafeException extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('內用座位只能使用內用杯喔。');
    }
}
