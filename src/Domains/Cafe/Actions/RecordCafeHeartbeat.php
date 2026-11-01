<?php

declare(strict_types=1);

namespace NouTools\Domains\Cafe\Actions;

use App\Enums\StudyRoomVenue;
use NouTools\Domains\Schedules\ValueObjects\StudentScheduleCookie;
use NouTools\Domains\StudyRoom\Actions\RecordHeartbeat;

final readonly class RecordCafeHeartbeat
{
    public function __construct(private RecordHeartbeat $recordHeartbeat) {}

    public function __invoke(StudentScheduleCookie $viewer): bool
    {
        return ($this->recordHeartbeat)($viewer, StudyRoomVenue::Cafe);
    }
}
