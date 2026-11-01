<?php

declare(strict_types=1);

namespace NouTools\Domains\Cafe\Exceptions;

use RuntimeException;

final class DineInNeedsSeatException extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('內用請先找位子坐喔。');
    }
}
