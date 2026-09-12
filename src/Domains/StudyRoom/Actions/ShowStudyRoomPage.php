<?php

declare(strict_types=1);

namespace NouTools\Domains\StudyRoom\Actions;

use App\Enums\StudyActivityVerb;
use App\Models\StudentSchedule;
use App\Models\StudyRoomProfile;
use App\Models\StudyRoomSeat;
use App\Settings\StudyRoomSettings;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Date;
use NouTools\Domains\StudyRoom\PageData\StudyRoomPageData;
use NouTools\Domains\StudyRoom\ValueObjects\PomodoroCycle;
use NouTools\Domains\StudyRoom\ViewModels\StudyRoomPomodoroCycleViewModel;
use NouTools\Domains\StudyRoom\ViewModels\StudyRoomProfileViewModel;
use NouTools\Domains\StudyRoom\ViewModels\StudyRoomSubjectViewModel;
use NouTools\Domains\StudyRoom\ViewModels\StudyRoomVerbViewModel;
use Spatie\LaravelData\DataCollection;

final readonly class ShowStudyRoomPage
{
    public function __construct(
        private SyncStudyRoomSeats $syncStudyRoomSeats,
        private BuildStudyRoomState $buildStudyRoomState,
        private ListStudyRoomSubjects $listStudyRoomSubjects,
        private RenderStudyRoomAnnouncement $renderStudyRoomAnnouncement,
        private StudyRoomSettings $studyRoomSettings,
    ) {}

    public function __invoke(Request $request): StudyRoomPageData
    {
        if (StudyRoomSeat::query()->count() === 0) {
            ($this->syncStudyRoomSeats)();
        }

        $viewerCookie = $request->studentScheduleFromCookie();
        $schedule = $viewerCookie === null ? null : StudentSchedule::query()->find($viewerCookie->id);
        $hasSchedule = $schedule !== null;

        $profile = $hasSchedule ? $schedule->studyRoomProfile : null;

        return new StudyRoomPageData(
            roomState: ($this->buildStudyRoomState)($viewerCookie),
            profile: $this->buildProfileViewModel($profile),
            subjects: StudyRoomSubjectViewModel::collect(
                $hasSchedule ? ($this->listStudyRoomSubjects)($schedule) : [],
                DataCollection::class,
            ),
            verbs: StudyRoomVerbViewModel::collect($this->buildVerbs(), DataCollection::class),
            announcementHtml: ($this->renderStudyRoomAnnouncement)($this->studyRoomSettings->announcement),
            openHoursLabel: (string) config('study-room.open_hours_label'),
            hasSchedule: $hasSchedule,
            needsProfile: $hasSchedule && ($profile === null || $profile->nickname === null),
            emojiChoices: (array) config('study-room.emojis'),
            isOpen: $this->studyRoomSettings->isOpen,
            clientConfig: $this->buildClientConfig(),
        );
    }

    private function buildProfileViewModel(?StudyRoomProfile $profile): StudyRoomProfileViewModel
    {
        $cooldownDays = (int) config('study-room.nickname.cooldown_days');
        $canChangeNicknameAt = $profile?->nickname_changed_at?->addDays($cooldownDays);

        return new StudyRoomProfileViewModel(
            nickname: $profile?->nickname,
            emoji: $profile?->emoji,
            nicknameChangedAt: $profile?->nickname_changed_at,
            canChangeNicknameAt: $canChangeNicknameAt,
            canChangeNickname: $canChangeNicknameAt === null || Date::now()->greaterThanOrEqualTo($canChangeNicknameAt),
            pomodoroCycle: StudyRoomPomodoroCycleViewModel::fromCycle(PomodoroCycle::forProfile($profile)),
            playSoundOnTimerEnd: $profile?->play_sound_on_timer_end ?? true,
        );
    }

    /**
     * @return array<int, StudyRoomVerbViewModel>
     */
    private function buildVerbs(): array
    {
        return array_map(
            fn (StudyActivityVerb $verb): StudyRoomVerbViewModel => new StudyRoomVerbViewModel(
                value: $verb->value,
                label: $verb->label(),
            ),
            StudyActivityVerb::cases(),
        );
    }

    /**
     * @return array<string, int|float|array<int, int>>
     */
    private function buildClientConfig(): array
    {
        $bounds = config('study-room.timer.pomodoro.bounds');

        return [
            'heartbeatIntervalSeconds' => (int) config('study-room.heartbeat.interval_seconds'),
            'heartbeatIdleReleaseSeconds' => (int) config('study-room.heartbeat.idle_release_seconds'),
            'realtimeConnectTimeoutSeconds' => (int) config('study-room.realtime.connect_timeout_seconds'),
            'timerPomodoroFocusMinutes' => (int) config('study-room.timer.pomodoro.focus_minutes'),
            'timerPomodoroShortBreakMinutes' => (int) config('study-room.timer.pomodoro.short_break_minutes'),
            'timerPomodoroLongBreakMinutes' => (int) config('study-room.timer.pomodoro.long_break_minutes'),
            'timerPomodoroRoundsPerCycle' => (int) config('study-room.timer.pomodoro.rounds_per_cycle'),
            'timerPomodoroFocusBounds' => array_map(intval(...), $bounds['focus_minutes']),
            'timerPomodoroBreakBounds' => array_map(intval(...), $bounds['break_minutes']),
            'timerPomodoroRoundsBounds' => array_map(intval(...), $bounds['rounds_per_cycle']),
            'timerCustomMinMinutes' => (int) config('study-room.timer.custom.min_minutes'),
            'timerCustomMaxMinutes' => (int) config('study-room.timer.custom.max_minutes'),
            'timerMaxSessionSeconds' => (int) config('study-room.timer.max_session_seconds'),
            'maxFloors' => (int) config('study-room.floors.max'),
            'latitude' => (float) config('study-room.location.latitude'),
            'longitude' => (float) config('study-room.location.longitude'),
        ];
    }
}
