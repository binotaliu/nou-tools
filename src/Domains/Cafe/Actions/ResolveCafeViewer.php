<?php

declare(strict_types=1);

namespace NouTools\Domains\Cafe\Actions;

use App\Models\StudyRoomProfile;
use Illuminate\Http\Request;
use NouTools\Domains\Schedules\ValueObjects\StudentScheduleCookie;

/**
 * The signed-in schedule if it has also picked a 自習室 nickname (the café
 * shows it), otherwise null.
 */
final readonly class ResolveCafeViewer
{
    public function __invoke(Request $request): ?StudentScheduleCookie
    {
        $viewer = $request->studentScheduleFromCookie();

        if ($viewer === null) {
            return null;
        }

        $hasNickname = StudyRoomProfile::query()
            ->where('student_schedule_id', $viewer->id)
            ->whereNotNull('nickname')
            ->exists();

        return $hasNickname ? $viewer : null;
    }
}
