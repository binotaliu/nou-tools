<?php

declare(strict_types=1);

namespace NouTools\Domains\GradeCalculator\Support;

/**
 * Summer terms (e.g. 2026C) have no midterm exam.
 */
final class IsSummerTerm
{
    public static function check(string $term): bool
    {
        return str_ends_with($term, 'C');
    }
}
