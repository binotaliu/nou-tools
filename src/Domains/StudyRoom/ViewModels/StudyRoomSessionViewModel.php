<?php

declare(strict_types=1);

namespace NouTools\Domains\StudyRoom\ViewModels;

use App\Models\StudyRoomSession;
use DateTimeInterface;
use Spatie\LaravelData\Data;

final class StudyRoomSessionViewModel extends Data
{
    public function __construct(
        public string $activityLabel,
        public ?string $subjectLabel,
        public DateTimeInterface $startedAt,
        public ?DateTimeInterface $endedAt,
        public int $focusSeconds,
        public bool $wasCompleted,
    ) {}

    public static function fromModel(StudyRoomSession $session): self
    {
        $subject = $session->subject_label ?? $session->subjectCourse?->name ?? '';

        return new self(
            activityLabel: $session->activity_verb?->format($subject) ?? '未設定活動',
            subjectLabel: $session->subject_label ?? $session->subjectCourse?->name,
            startedAt: $session->started_at,
            endedAt: $session->ended_at,
            focusSeconds: $session->focus_seconds,
            wasCompleted: $session->was_completed,
        );
    }
}
