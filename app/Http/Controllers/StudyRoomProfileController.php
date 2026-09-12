<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use NouTools\Domains\StudyRoom\Actions\SetStudyRoomProfile;
use NouTools\Domains\StudyRoom\DataTransferObjects\SetStudyRoomProfileData;
use NouTools\Domains\StudyRoom\Exceptions\NicknameCooldownException;

final class StudyRoomProfileController extends Controller
{
    public function __invoke(SetStudyRoomProfileData $input, Request $request, SetStudyRoomProfile $setStudyRoomProfile): RedirectResponse
    {
        $viewer = $request->studentScheduleFromCookie();

        if ($viewer === null) {
            return redirect()->route('schedules.create');
        }

        try {
            $setStudyRoomProfile($viewer, $input);
        } catch (NicknameCooldownException $exception) {
            return redirect()->back()->withErrors([
                'nickname' => '暱稱每 '.config('study-room.nickname.cooldown_days').' 天才能變更一次，下次可以變更的日期是 '.$exception->canChangeNicknameAt->format('Y-m-d').'。',
            ]);
        }

        return redirect()->back()->with('success', '暱稱已更新');
    }
}
