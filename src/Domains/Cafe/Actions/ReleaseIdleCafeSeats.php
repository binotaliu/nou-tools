<?php

declare(strict_types=1);

namespace NouTools\Domains\Cafe\Actions;

use App\Enums\StudyRoomVenue;
use NouTools\Domains\StudyRoom\Actions\ReleaseIdleSeats;

/**
 * The café's sweep: the study room's idle release over the café seats (with
 * the café's own threshold), plus seats whose student ordered nothing.
 */
final readonly class ReleaseIdleCafeSeats
{
    public function __construct(
        private ReleaseIdleSeats $releaseIdleSeats,
        private ReleaseUnorderedCafeSeats $releaseUnorderedCafeSeats,
    ) {}

    public function __invoke(): int
    {
        return ($this->releaseIdleSeats)(StudyRoomVenue::Cafe) + ($this->releaseUnorderedCafeSeats)();
    }
}
