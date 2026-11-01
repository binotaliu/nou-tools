<?php

declare(strict_types=1);

namespace NouTools\Domains\Acorns\Exceptions;

use RuntimeException;

final class InsufficientAcornsException extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('橡實不夠囉。');
    }
}
