<?php

declare(strict_types=1);

namespace NouTools\Domains\Cafe\Exceptions;

use RuntimeException;

final class CafeAlreadySeatedException extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('你已經坐在咖啡廳的座位上了。');
    }
}
