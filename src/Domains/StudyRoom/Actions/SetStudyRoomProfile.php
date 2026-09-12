<?php

declare(strict_types=1);

namespace NouTools\Domains\StudyRoom\Actions;

use App\Models\StudyRoomProfile;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use NouTools\Domains\Schedules\ValueObjects\StudentScheduleCookie;
use NouTools\Domains\StudyRoom\DataTransferObjects\SetStudyRoomProfileData;
use NouTools\Domains\StudyRoom\Exceptions\NicknameCooldownException;

/**
 * Creates or updates the viewer's 自習室 profile. The emoji is never
 * cooldowned — only an actual nickname change is, and only when it's
 * changing to something different from what's already saved (re-submitting
 * the same nickname, or changing only the emoji, is always allowed).
 */
final readonly class SetStudyRoomProfile
{
    public function __invoke(StudentScheduleCookie $viewer, SetStudyRoomProfileData $data): StudyRoomProfile
    {
        return DB::transaction(function () use ($viewer, $data): StudyRoomProfile {
            $profile = StudyRoomProfile::query()->firstOrNew(['student_schedule_id' => $viewer->id]);

            $isNicknameChanging = $profile->nickname !== null && $profile->nickname !== $data->nickname;

            if ($isNicknameChanging) {
                $this->guardCooldown($profile);
            }

            $profile->student_schedule_id = $viewer->id;
            $profile->emoji = $data->emoji;
            $profile->play_sound_on_timer_end = $data->playSoundOnTimerEnd;

            if ($profile->nickname === null || $isNicknameChanging) {
                $profile->nickname = $data->nickname;
                $profile->nickname_changed_at = Date::now();
            }

            $profile->saveOrFail();

            return $profile;
        });
    }

    private function guardCooldown(StudyRoomProfile $profile): void
    {
        if ($profile->nickname_changed_at === null) {
            return;
        }

        $cooldownDays = (int) config('study-room.nickname.cooldown_days');
        $canChangeAt = $profile->nickname_changed_at->addDays($cooldownDays);

        if (Date::now()->lessThan($canChangeAt)) {
            throw new NicknameCooldownException($canChangeAt);
        }
    }
}
