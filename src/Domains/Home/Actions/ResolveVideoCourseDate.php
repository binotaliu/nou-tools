<?php

declare(strict_types=1);

namespace NouTools\Domains\Home\Actions;

use Illuminate\Support\Facades\Date;

/**
 * Normalises the requested `date` query to Y-m-d in Taipei time, falling back to today.
 */
final readonly class ResolveVideoCourseDate
{
    public function __invoke(?string $date): string
    {
        try {
            return $date
                ? Date::createFromFormat('Y-m-d', $date, 'Asia/Taipei')->format('Y-m-d')
                : Date::now('Asia/Taipei')->format('Y-m-d');
        } catch (\Exception) {
            return Date::now('Asia/Taipei')->format('Y-m-d');
        }
    }
}
