<?php

declare(strict_types=1);

namespace NouTools\Domains\Cafe\Actions;

use App\Enums\StudyRoomVenue;
use App\Models\StudyRoomProfile;
use App\Models\StudyRoomSeat;
use Illuminate\Http\Request;
use NouTools\Domains\Cafe\ViewModels\CafePageViewModel;

final readonly class ShowCafePage
{
    public function __construct(private SyncCafeSeats $syncCafeSeats) {}

    public function __invoke(Request $request): CafePageViewModel
    {
        if (StudyRoomSeat::query()->inRoom(StudyRoomVenue::Cafe)->count() === 0) {
            ($this->syncCafeSeats)();
        }

        $viewer = $request->studentScheduleFromCookie();
        $profile = $viewer === null
            ? null
            : StudyRoomProfile::query()->where('student_schedule_id', $viewer->id)->first();

        return new CafePageViewModel(
            hasSchedule: $viewer !== null,
            hasNickname: $profile?->nickname !== null,
            nickname: $profile?->nickname,
            emoji: $profile?->emoji,
            heartbeatIntervalSeconds: (int) config('cafe.heartbeat.interval_seconds'),
            connectTimeoutSeconds: (int) config('study-room.realtime.connect_timeout_seconds'),
            notePrice: (int) config('cafe.notes.price'),
            noteMaxLength: (int) config('cafe.notes.max_text_length'),
            noteLifetimeDays: (int) config('cafe.notes.lifetime_days'),
        );
    }
}
