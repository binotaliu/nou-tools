<?php

declare(strict_types=1);

namespace NouTools\Domains\Cafe\Exceptions;

use RuntimeException;

final class TakeawayInCafeException extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('坐在咖啡廳裡只能內用喔。');
    }
}
