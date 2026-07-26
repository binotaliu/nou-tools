<?php

declare(strict_types=1);

namespace NouTools\Domains\LearningProgress\ViewModels;

use Spatie\LaravelData\Data;

final class LearningProgressCourseViewModel extends Data
{
    public function __construct(
        public int $id,
        public ?string $code,
        public string $name,
    ) {}

    /**
     * @param  array{id: int, code?: ?string, name: string}  $course
     */
    public static function fromArray(array $course): self
    {
        return new self(
            id: $course['id'],
            code: $course['code'] ?? null,
            name: $course['name'],
        );
    }
}
