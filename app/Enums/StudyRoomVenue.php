<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Which room a `study_room_seats` row belongs to. The café shares the study
 * room's seat table so the focus timer, sessions, acorns and pushes work
 * there unchanged; a student holds one seat across both.
 */
enum StudyRoomVenue: string
{
    case Study = 'study';
    case Cafe = 'cafe';
}
