<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\StudyRoomProfile;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Date;
use NouTools\Domains\StudyRoom\Actions\SetStudyRoomProfile;
use NouTools\Domains\StudyRoom\DataTransferObjects\SetStudyRoomProfileData;
use NouTools\Domains\StudyRoom\Exceptions\NicknameCooldownException;
use NouTools\Domains\StudyRoom\ValueObjects\PomodoroCycle;
use NouTools\Domains\StudyRoom\ViewModels\StudyRoomPomodoroCycleViewModel;
use NouTools\Domains\StudyRoom\ViewModels\StudyRoomProfileViewModel;

final class StudyRoomProfileController extends Controller
{
    /**
     * Called directly via fetch/axios from the Vue page (resources/js/Pages/StudyRoom/Show.vue),
     * not through a Blade form submit, so the response is JSON rather than
     * a redirect back to the page.
     */
    public function __invoke(SetStudyRoomProfileData $input, Request $request, SetStudyRoomProfile $setStudyRoomProfile): JsonResponse
    {
        $viewer = $request->studentScheduleFromCookie();

        if ($viewer === null) {
            return response()->json(['message' => '請先建立課表。', 'redirect' => route('schedules.create')], 403);
        }

        try {
            $profile = $setStudyRoomProfile($viewer, $input);
        } catch (NicknameCooldownException $exception) {
            return response()->json([
                'message' => '暱稱每 '.config('study-room.nickname.cooldown_days').' 天才能變更一次，下次可以變更的日期是 '.$exception->canChangeNicknameAt->format('Y-m-d').'。',
                'errors' => [
                    'nickname' => ['暱稱每 '.config('study-room.nickname.cooldown_days').' 天才能變更一次，下次可以變更的日期是 '.$exception->canChangeNicknameAt->format('Y-m-d').'。'],
                ],
            ], 422);
        }

        return response()->json([
            'ok' => true,
            'message' => '暱稱已更新',
            'profile' => $this->buildProfileViewModel($profile),
        ]);
    }

    private function buildProfileViewModel(StudyRoomProfile $profile): StudyRoomProfileViewModel
    {
        $cooldownDays = (int) config('study-room.nickname.cooldown_days');
        $canChangeNicknameAt = $profile->nickname_changed_at?->addDays($cooldownDays);

        return new StudyRoomProfileViewModel(
            nickname: $profile->nickname,
            emoji: $profile->emoji,
            nicknameChangedAt: $profile->nickname_changed_at,
            canChangeNicknameAt: $canChangeNicknameAt,
            canChangeNickname: $canChangeNicknameAt === null || Date::now()->greaterThanOrEqualTo($canChangeNicknameAt),
            pomodoroCycle: StudyRoomPomodoroCycleViewModel::fromCycle(PomodoroCycle::forProfile($profile)),
            playSoundOnTimerEnd: $profile->play_sound_on_timer_end,
            notifyOnTimerEnd: $profile->notify_on_timer_end,
        );
    }
}
