<?php

declare(strict_types=1);

namespace NouTools\Domains\StudyRoom\Actions;

use getID3;
use Throwable;

/**
 * Length of a local audio file (mp3 / ogg) in whole seconds, or null when the
 * file is missing or its duration can't be determined.
 */
final readonly class ReadAudioDuration
{
    public function __invoke(string $path): ?int
    {
        if (! is_file($path)) {
            return null;
        }

        try {
            $info = (new getID3)->analyze($path);
        } catch (Throwable) {
            return null;
        }

        $seconds = $info['playtime_seconds'] ?? null;

        return is_numeric($seconds) && $seconds > 0 ? (int) round((float) $seconds) : null;
    }
}
