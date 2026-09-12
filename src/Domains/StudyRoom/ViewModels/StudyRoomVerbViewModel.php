<?php

declare(strict_types=1);

namespace NouTools\Domains\StudyRoom\ViewModels;

use Spatie\LaravelData\Data;

/**
 * A selectable activity verb (e.g. 正在複習…), derived from
 * `App\Enums\StudyActivityVerb`.
 */
final class StudyRoomVerbViewModel extends Data
{
    public function __construct(
        public string $value,
        public string $label,
    ) {}
}
