<?php

declare(strict_types=1);

namespace NouTools\Domains\Cafe\Actions;

use App\Models\CafeSeat;
use Illuminate\Support\Facades\Date;
use NouTools\Domains\Cafe\ViewModels\CafeSeatViewModel;
use NouTools\Domains\Cafe\ViewModels\CafeStateViewModel;
use NouTools\Domains\Schedules\ValueObjects\StudentScheduleCookie;

final readonly class BuildCafeState
{
    public function __invoke(?StudentScheduleCookie $viewer): CafeStateViewModel
    {
        $seats = CafeSeat::query()
            ->with('schedule.studyRoomProfile')
            ->orderBy('id')
            ->get();

        $viewerId = $viewer?->id;
        $viewModels = $seats
            ->map(fn (CafeSeat $seat): CafeSeatViewModel => CafeSeatViewModel::fromModel($seat, $viewerId))
            ->all();

        // `isYou` is viewer-relative and must not move the version, or two
        // viewers of the same room would never share an ETag.
        $signature = $seats
            ->map(fn (CafeSeat $seat): string => implode('|', [
                $seat->code,
                $seat->student_schedule_id ?? '-',
                $seat->schedule?->studyRoomProfile?->nickname ?? '-',
                $seat->schedule?->studyRoomProfile?->emoji ?? '-',
            ]))
            ->implode(';');

        return new CafeStateViewModel(
            seats: $viewModels,
            occupantCount: $seats->whereNotNull('student_schedule_id')->count(),
            serverTime: Date::now()->toIso8601String(),
            version: hash('xxh128', $signature),
        );
    }
}
