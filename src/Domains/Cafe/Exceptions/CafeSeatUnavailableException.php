<?php

declare(strict_types=1);

namespace NouTools\Domains\Cafe\Exceptions;

use RuntimeException;

final class CafeSeatUnavailableException extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('這個座位剛剛被別人坐走了。');
    }
}
