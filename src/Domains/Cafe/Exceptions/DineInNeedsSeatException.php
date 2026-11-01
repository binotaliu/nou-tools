<?php

declare(strict_types=1);

namespace NouTools\Domains\Cafe\Exceptions;

use RuntimeException;

final class DineInNeedsSeatException extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('內用要先在咖啡廳入座喔，或改點外帶。');
    }
}
