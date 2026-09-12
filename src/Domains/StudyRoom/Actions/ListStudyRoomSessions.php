<?php

declare(strict_types=1);

namespace NouTools\Domains\StudyRoom\Actions;

use App\Models\StudyRoomSession;
use NouTools\Domains\Schedules\ValueObjects\StudentScheduleCookie;
use NouTools\Domains\StudyRoom\ViewModels\StudyRoomSessionViewModel;

/**
 * The viewer's own most recent focus sessions, newest first, for the
 * PersonalInfo modal's session log.
 */
final readonly class ListStudyRoomSessions
{
    public function __invoke(StudentScheduleCookie $viewer, int $limit = 10): array
    {
        return StudyRoomSession::query()
            ->where('student_schedule_id', $viewer->id)
            ->orderByDesc('started_at')
            ->limit($limit)
            ->get()
            ->map(fn (StudyRoomSession $session): StudyRoomSessionViewModel => StudyRoomSessionViewModel::fromModel($session))
            ->all();
    }
}
