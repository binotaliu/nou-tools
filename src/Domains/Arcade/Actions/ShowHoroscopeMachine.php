<?php

declare(strict_types=1);

namespace NouTools\Domains\Arcade\Actions;

use App\Enums\ArcadeGame;
use App\Enums\ZodiacSign;
use App\Models\ArcadePlay;
use Illuminate\Support\Facades\Date;
use NouTools\Domains\Acorns\Support\AcornExpiry;
use NouTools\Domains\Arcade\ViewModels\HoroscopeMachineViewModel;
use NouTools\Domains\Arcade\ViewModels\HoroscopeReadingViewModel;
use NouTools\Domains\Schedules\ValueObjects\StudentScheduleCookie;

final readonly class ShowHoroscopeMachine
{
    public function __invoke(?StudentScheduleCookie $viewer): HoroscopeMachineViewModel
    {
        $today = AcornExpiry::localDay(Date::now())->toDateString();

        $unlocked = $viewer === null
            ? []
            : ArcadePlay::query()
                ->where('student_schedule_id', $viewer->id)
                ->where('game', ArcadeGame::Horoscope)
                ->where('played_on', $today)
                ->orderBy('id')
                ->get()
                ->map(fn (ArcadePlay $play): HoroscopeReadingViewModel => HoroscopeReadingViewModel::fromPlay($play))
                ->all();

        return new HoroscopeMachineViewModel(
            price: (int) config('cafe.arcade.horoscope.price'),
            today: $today,
            signs: array_map(fn (ZodiacSign $sign): array => [
                'value' => $sign->value,
                'label' => $sign->label(),
                'symbol' => $sign->symbol(),
                'dateRange' => $sign->dateRange(),
            ], ZodiacSign::cases()),
            unlocked: $unlocked,
        );
    }
}
