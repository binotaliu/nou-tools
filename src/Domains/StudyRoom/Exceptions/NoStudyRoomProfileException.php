<?php

declare(strict_types=1);

namespace NouTools\Domains\StudyRoom\Exceptions;

use RuntimeException;

/**
 * Thrown when a study-room preference is changed by a student who has not
 * chosen a nickname yet, so there is no profile to hold it.
 */
final class NoStudyRoomProfileException extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('請先到自習室設定暱稱。');
    }
}
