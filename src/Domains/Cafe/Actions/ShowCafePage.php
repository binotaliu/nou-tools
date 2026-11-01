<?php

declare(strict_types=1);

namespace NouTools\Domains\Cafe\Actions;

use App\Enums\StudyRoomVenue;
use App\Models\StudentSchedule;
use App\Models\StudyRoomProfile;
use App\Models\StudyRoomSeat;
use Illuminate\Http\Request;
use NouTools\Domains\Cafe\ViewModels\CafePageViewModel;
use NouTools\Domains\StudyRoom\Actions\BuildStudyRoomClientConfig;
use NouTools\Domains\StudyRoom\Actions\ListStudyRoomSubjects;
use NouTools\Domains\StudyRoom\Actions\ListStudyRoomVerbs;
use NouTools\Domains\StudyRoom\ValueObjects\PomodoroCycle;
use NouTools\Domains\StudyRoom\ViewModels\StudyRoomPomodoroCycleViewModel;
use NouTools\Domains\StudyRoom\ViewModels\StudyRoomSubjectViewModel;
use NouTools\Domains\StudyRoom\ViewModels\StudyRoomVerbViewModel;
use Spatie\LaravelData\DataCollection;

final readonly class ShowCafePage
{
    public function __construct(
        private SyncCafeSeats $syncCafeSeats,
        private ListStudyRoomSubjects $listStudyRoomSubjects,
        private ListStudyRoomVerbs $listStudyRoomVerbs,
        private BuildStudyRoomClientConfig $buildStudyRoomClientConfig,
    ) {}

    public function __invoke(Request $request): CafePageViewModel
    {
        if (StudyRoomSeat::query()->inRoom(StudyRoomVenue::Cafe)->count() === 0) {
            ($this->syncCafeSeats)();
        }

        $viewer = $request->studentScheduleFromCookie();
        $schedule = $viewer === null ? null : StudentSchedule::query()->find($viewer->id);
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
            orderGraceSeconds: (int) config('cafe.seat.order_grace_seconds'),
            pomodoroCycle: StudyRoomPomodoroCycleViewModel::fromCycle(PomodoroCycle::forProfile($profile)),
            playSoundOnTimerEnd: $profile?->play_sound_on_timer_end ?? true,
            verbs: StudyRoomVerbViewModel::collect(($this->listStudyRoomVerbs)(), DataCollection::class)->toArray(),
            subjects: StudyRoomSubjectViewModel::collect($schedule === null ? [] : ($this->listStudyRoomSubjects)($schedule), DataCollection::class)->toArray(),
            timerConfig: ($this->buildStudyRoomClientConfig)(),
        );
    }
}
