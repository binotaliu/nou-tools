<?php

declare(strict_types=1);

namespace NouTools\Domains\Articles\Markdown\Dialogue;

final readonly class PersonaResolver
{
    private const FALLBACK_SLUG = 'neutral';

    public function resolve(string $speakerName): ResolvedPersona
    {
        /** @var array<string, array{names: array<int, string>, avatar?: string, moods?: array<string, string>}> $personas */
        $personas = config('dialogue.personas', []);

        foreach ($personas as $slug => $persona) {
            if (\in_array($speakerName, $persona['names'] ?? [], true)) {
                return new ResolvedPersona(
                    slug: $slug,
                    avatar: $persona['avatar'] ?? null,
                    moods: $persona['moods'] ?? [],
                );
            }
        }

        return new ResolvedPersona(
            slug: self::FALLBACK_SLUG,
            avatar: mb_substr($speakerName, 0, 1) ?: '💬',
            moods: [],
        );
    }
}
