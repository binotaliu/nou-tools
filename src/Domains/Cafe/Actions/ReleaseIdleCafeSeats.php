<?php

declare(strict_types=1);

namespace NouTools\Domains\Cafe\Actions;

use App\Enums\StudyRoomVenue;
use NouTools\Domains\StudyRoom\Actions\ReleaseIdleSeats;

/**
 * The café's idle sweep: the same release as the study room's, over the café
 * seats and with the café's own threshold.
 */
final readonly class ReleaseIdleCafeSeats
{
    public function __construct(private ReleaseIdleSeats $releaseIdleSeats) {}

    public function __invoke(): int
    {
        return ($this->releaseIdleSeats)(StudyRoomVenue::Cafe);
    }
}
