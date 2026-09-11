<?php

declare(strict_types=1);

namespace NouTools\Domains\StudyRoom\ViewModels;

use App\Enums\StudySeatKind;
use App\Enums\StudyTimerMode;
use App\Enums\StudyTimerPhase;
use App\Models\StudyRoomSeat;
use DateTimeInterface;
use Spatie\LaravelData\Data;

/**
 * Public projection of a seat. Deliberately never exposes
 * `student_schedule_id`, the schedule's uuid, or its route token — seats
 * are identified publicly by `code` only, since the cookie token is the
 * viewer's identity and must not leak here.
 */
final class StudyRoomSeatViewModel extends Data
{
    public function __construct(
        public string $code,
        public StudySeatKind $kind,
        public ?string $groupCode,
        public int $seatNumber,
        public string $label,
        public bool $isOccupied,
        public bool $isYou,
        public ?string $nickname,
        public ?string $emoji,
        public ?string $activity,
        public ?StudyTimerMode $timerMode,
        public ?StudyTimerPhase $timerPhase,
        public ?DateTimeInterface $timerEndsAt,
        public ?DateTimeInterface $timerStartedAt,
    ) {}

    public static function fromModel(StudyRoomSeat $seat, ?int $viewerScheduleId): self
    {
        $profile = $seat->schedule?->studyRoomProfile;
        $subject = $seat->subject_label ?? $seat->subjectCourse?->name;

        return new self(
            code: $seat->code,
            kind: $seat->kind,
            groupCode: $seat->group_code,
            seatNumber: $seat->seat_number,
            label: $seat->label,
            isOccupied: $seat->student_schedule_id !== null,
            isYou: $viewerScheduleId !== null && $seat->student_schedule_id === $viewerScheduleId,
            nickname: $profile?->nickname,
            emoji: $profile?->emoji,
            activity: $seat->activity_verb && $subject ? $seat->activity_verb->format($subject) : null,
            timerMode: $seat->timer_mode,
            timerPhase: $seat->timer_phase,
            timerEndsAt: $seat->timer_ends_at,
            timerStartedAt: $seat->timer_started_at,
        );
    }
}
