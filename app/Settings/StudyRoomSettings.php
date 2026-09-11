<?php

declare(strict_types=1);

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

final class StudyRoomSettings extends Settings
{
    public string $announcement;

    /** @var array<int, string> */
    public array $forbiddenNicknames;

    public bool $isOpen;

    public static function group(): string
    {
        return 'study_room';
    }
}
