<?php

declare(strict_types=1);

namespace NouTools\Domains\StudyRoom\Exceptions;

use DateTimeInterface;
use RuntimeException;

/**
 * Thrown by `SetStudyRoomProfile` when a student tries to actually change
 * their nickname (not just the emoji, and not to the same nickname) before
 * `study-room.nickname.cooldown_days` has elapsed since their last change.
 */
final class NicknameCooldownException extends RuntimeException
{
    public function __construct(public readonly DateTimeInterface $canChangeNicknameAt)
    {
        parent::__construct('暱稱尚在冷卻期間，無法變更。');
    }
}
