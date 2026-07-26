<?php

declare(strict_types=1);

namespace NouTools\Domains\Articles\Markdown\Dialogue;

final class ResolvedPersona
{
    /**
     * @param  array<string, string>  $moods
     */
    public function __construct(
        public readonly string $slug,
        public readonly ?string $avatar,
        public readonly array $moods,
    ) {}

    public function moodAvatar(?string $mood): ?string
    {
        if ($mood === null) {
            return $this->avatar;
        }

        return $this->moods[$mood] ?? $this->avatar;
    }
}
