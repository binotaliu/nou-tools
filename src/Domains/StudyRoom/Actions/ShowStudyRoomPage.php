<?php

declare(strict_types=1);

namespace NouTools\Domains\StudyRoom\Actions;

use App\Enums\StudyRoomVenue;
use App\Models\StudentSchedule;
use App\Models\StudyRoomProfile;
use App\Models\StudyRoomSeat;
use App\Settings\StudyRoomSettings;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Date;
use NouTools\Domains\StudyRoom\PageData\StudyRoomPageData;
use NouTools\Domains\StudyRoom\ValueObjects\PomodoroCycle;
use NouTools\Domains\StudyRoom\ViewModels\StudyRoomGoalViewModel;
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
        private ListStudyRoomVerbs $listStudyRoomVerbs,
        private BuildStudyRoomClientConfig $buildStudyRoomClientConfig,
    ) {}

    public function __invoke(Request $request): StudyRoomPageData
    {
        if (StudyRoomSeat::query()->inRoom(StudyRoomVenue::Study)->count() === 0) {
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
            verbs: StudyRoomVerbViewModel::collect(($this->listStudyRoomVerbs)(), DataCollection::class),
            announcementHtml: ($this->renderStudyRoomAnnouncement)($this->studyRoomSettings->announcement),
            openHoursLabel: (string) config('study-room.open_hours_label'),
            hasSchedule: $hasSchedule,
            needsProfile: $hasSchedule && ($profile === null || $profile->nickname === null),
            emojiChoices: (array) config('study-room.emojis'),
            isOpen: $this->studyRoomSettings->isOpen,
            clientConfig: ($this->buildStudyRoomClientConfig)(),
            vapidPublicKey: config('webpush.vapid.public_key'),
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
            notifyOnTimerEnd: $profile?->notify_on_timer_end ?? false,
            goal: StudyRoomGoalViewModel::fromProfile($profile),
        );
    }
}
