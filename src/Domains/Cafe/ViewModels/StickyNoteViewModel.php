<?php

declare(strict_types=1);

namespace NouTools\Domains\Cafe\ViewModels;

use App\Models\CafeStickyNote;
use Spatie\LaravelData\Data;

/**
 * A note as the wall shows it. Never carries the schedule's identity; the
 * drawing is a route, not a storage path.
 */
final class StickyNoteViewModel extends Data
{
    public function __construct(
        public int $id,
        public ?string $text,
        public ?string $drawingUrl,
        public string $paperColor,
        public float $x,
        public float $y,
        public int $rotation,
        public ?string $nickname,
        public ?string $emoji,
        public ?string $publishedAt,
        public bool $isMine,
        public string $status,
        public bool $canReport,
    ) {}

    public static function fromModel(CafeStickyNote $note, ?int $viewerScheduleId): self
    {
        $profile = $note->schedule?->studyRoomProfile;
        $isMine = $viewerScheduleId !== null && $note->student_schedule_id === $viewerScheduleId;

        return new self(
            id: $note->id,
            text: $note->text,
            drawingUrl: $note->drawing_path === null ? null : route('cafe.notes.drawing', $note),
            paperColor: $note->paper_color,
            x: $note->x,
            y: $note->y,
            rotation: $note->rotation,
            nickname: $profile?->nickname,
            emoji: $profile?->emoji,
            publishedAt: $note->published_at?->toIso8601String(),
            isMine: $isMine,
            status: $note->status->value,
            canReport: ! $isMine && $note->status->value === 'published',
        );
    }
}
