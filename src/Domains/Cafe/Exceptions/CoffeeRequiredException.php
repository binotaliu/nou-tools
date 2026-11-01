<?php

declare(strict_types=1);

namespace NouTools\Domains\Cafe\Exceptions;

use RuntimeException;

final class CoffeeRequiredException extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('咖啡廳有低消，請先點餐再開始專注吧。');
    }
}
