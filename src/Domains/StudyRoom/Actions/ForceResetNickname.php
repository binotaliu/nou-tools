<?php

declare(strict_types=1);

namespace NouTools\Domains\StudyRoom\Actions;

use App\Models\StudyRoomProfile;
use App\Models\StudyRoomSeat;
use App\Models\User;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;

/**
 * An admin's moderation action for a nickname visible to the whole room.
 * Nulls `nickname` and `nickname_changed_at` — `SetStudyRoomProfile` only
 * enforces the cooldown when the profile already has a nickname
 * (`$profile->nickname !== null`), so clearing it lets the student pick a
 * new one immediately instead of waiting out the cooldown they didn't ask
 * to restart. `nickname_reset_at`/`nickname_reset_by` record who did this
 * and when, for accountability.
 *
 * If the profile's owner is currently seated, the offending nickname is
 * broadcast away immediately rather than lingering until the next poll.
 */
final readonly class ForceResetNickname
{
    public function __construct(
        private BroadcastStudyRoomChange $broadcastStudyRoomChange,
    ) {}

    public function __invoke(StudyRoomProfile $profile, User $actingAdmin): StudyRoomProfile
    {
        $seat = DB::transaction(function () use ($profile, $actingAdmin): ?StudyRoomSeat {
            $profile->nickname = null;
            $profile->nickname_changed_at = null;
            $profile->nickname_reset_at = Date::now();
            $profile->nickname_reset_by = $actingAdmin->id;
            $profile->saveOrFail();

            return StudyRoomSeat::query()
                ->where('student_schedule_id', $profile->student_schedule_id)
                ->first();
        });

        if ($seat !== null) {
            ($this->broadcastStudyRoomChange)('seat.updated', $seat);
        }

        return $profile;
    }
}
