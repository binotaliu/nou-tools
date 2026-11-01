<?php

declare(strict_types=1);

namespace NouTools\Domains\Cafe\Exceptions;

use RuntimeException;

final class NoCoffeeToHandleException extends RuntimeException
{
    public function __construct(string $message = '桌上沒有可以喝的飲品。')
    {
        parent::__construct($message);
    }
}
