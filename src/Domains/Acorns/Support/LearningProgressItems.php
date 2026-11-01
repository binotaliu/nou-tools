<?php

declare(strict_types=1);

namespace NouTools\Domains\Acorns\Support;

/**
 * Turns the learning-progress JSON blobs into the set of ticked items, each
 * with the acorns it is worth. Keys are stable across saves:
 * `{courseId}:w{week}:video|textbook` and `{courseId}:hw{number}`.
 */
final class LearningProgressItems
{
    /**
     * @param  array<array-key, mixed>  $progress
     * @param  array<array-key, mixed>  $homework
     * @return array<string, int> item key => acorns
     */
    public static function ticked(array $progress, array $homework): array
    {
        $items = [];
        $weekValue = (int) config('cafe.acorns.learning_progress.week_item');
        $homeworkValue = (int) config('cafe.acorns.learning_progress.homework');

        foreach ($progress as $courseId => $weeks) {
            if (! is_array($weeks)) {
                continue;
            }

            foreach ($weeks as $week => $entry) {
                if (! is_array($entry)) {
                    continue;
                }

                foreach (['video', 'textbook'] as $kind) {
                    if (self::isTicked($entry[$kind] ?? false)) {
                        $items["{$courseId}:w{$week}:{$kind}"] = $weekValue;
                    }
                }
            }
        }

        foreach ($homework as $courseId => $numbers) {
            if (! is_array($numbers)) {
                continue;
            }

            foreach ($numbers as $number => $entry) {
                if (is_array($entry) && self::isTicked($entry['completed'] ?? false)) {
                    $items["{$courseId}:hw{$number}"] = $homeworkValue;
                }
            }
        }

        return $items;
    }

    private static function isTicked(mixed $value): bool
    {
        return filter_var($value, FILTER_VALIDATE_BOOLEAN);
    }
}
