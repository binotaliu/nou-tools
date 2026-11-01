<?php

declare(strict_types=1);

namespace NouTools\Domains\Cafe\Exceptions;

use RuntimeException;

final class StickyNoteNotAllowedException extends RuntimeException
{
    public static function blocked(): self
    {
        return new self('店長似乎不想給你便利貼。');
    }

    public static function noCoffeeToday(): self
    {
        return new self('低消一杯飲料，才可購買便利貼。');
    }

    public static function alreadyPostedToday(): self
    {
        return new self('每人每天只能買一張便利貼。明天再來買吧。');
    }
}
