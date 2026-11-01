<?php

declare(strict_types=1);

namespace NouTools\Domains\Cafe\ViewModels;

use App\Enums\CafeSeatKind;
use App\Models\CafeSeat;
use Spatie\LaravelData\Data;

/**
 * Public projection of a café seat: identified by `code` only, never by the
 * schedule's id or uuid.
 */
final class CafeSeatViewModel extends Data
{
    public function __construct(
        public string $code,
        public CafeSeatKind $kind,
        public ?string $groupCode,
        public int $seatNumber,
        public string $label,
        public bool $isOccupied,
        public bool $isYou,
        public ?string $nickname,
        public ?string $emoji,
    ) {}

    public static function fromModel(CafeSeat $seat, ?int $viewerScheduleId): self
    {
        $profile = $seat->schedule?->studyRoomProfile;

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
        );
    }
}
