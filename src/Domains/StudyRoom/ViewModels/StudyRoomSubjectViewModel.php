<?php

declare(strict_types=1);

namespace NouTools\Domains\StudyRoom\ViewModels;

use Spatie\LaravelData\Data;

/**
 * One selectable "what are you studying" option, sourced from the viewer's
 * own schedule for the current term. `id` is null only for the 其他
 * sentinel, appended so a student whose subject isn't a course of theirs
 * can still say something.
 */
final class StudyRoomSubjectViewModel extends Data
{
    public function __construct(
        public ?int $id,
        public string $name,
    ) {}
}
