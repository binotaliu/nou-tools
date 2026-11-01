<?php

declare(strict_types=1);

namespace NouTools\Domains\Cafe\Exceptions;

use RuntimeException;

final class StickyNoteNotAllowedException extends RuntimeException
{
    public static function blocked(): self
    {
        return new self('這份課表目前不能張貼便利貼。');
    }

    public static function noCoffeeToday(): self
    {
        return new self('今天先點一杯飲品，才能跟店長買便利貼。');
    }

    public static function alreadyPostedToday(): self
    {
        return new self('今天已經貼過一張便利貼了，明天再來吧。');
    }
}
