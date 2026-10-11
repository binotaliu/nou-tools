<?php

declare(strict_types=1);

namespace NouTools\Domains\GradeCalculator\Support;

/**
 * 平時 / 期中考 / 期末考 shares of the 學期成績, in percent.
 */
final class DefaultWeights
{
    /**
     * @return array{regular: int, midterm: int, final: int}
     */
    public static function for(bool $isSummer): array
    {
        return $isSummer
            ? ['regular' => 30, 'midterm' => 0, 'final' => 70]
            : ['regular' => 30, 'midterm' => 30, 'final' => 40];
    }
}
