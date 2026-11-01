<?php

declare(strict_types=1);

namespace NouTools\Domains\Cafe\Exceptions;

use RuntimeException;

final class MenuItemUnavailableException extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('今日未提供此餐點。');
    }
}
