<?php

declare(strict_types=1);

namespace NouTools\Domains\Cafe\Exceptions;

use RuntimeException;

final class CoffeeRequiredException extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('在咖啡廳要先點一杯飲料，才能開始專注。');
    }
}
