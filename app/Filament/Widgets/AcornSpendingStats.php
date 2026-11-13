<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Models\AcornTransaction;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Date;
use NouTools\Domains\Acorns\Support\AcornExpiry;

/**
 * How many people actually spend their acorns (spends are the negative rows,
 * dated by the Taipei day they happened).
 */
final class AcornSpendingStats extends StatsOverviewWidget
{
    protected static ?int $sort = 25;

    protected ?string $heading = '橡實';

    /**
     * @return array<int, Stat>
     */
    protected function getStats(): array
    {
        $today = AcornExpiry::localDay(Date::now());
        $todayDate = $today->toDateString();
        $weekAgo = $today->copy()->subDays(6)->toDateString();

        $spendersSince = fn (string $from): int => AcornTransaction::query()
            ->where('amount', '<', 0)
            ->where('earned_on', '>=', $from)
            ->distinct()
            ->count('student_schedule_id');

        $spentSince = fn (string $from): int => abs((int) AcornTransaction::query()
            ->where('amount', '<', 0)
            ->where('earned_on', '>=', $from)
            ->sum('amount'));

        $holders = AcornTransaction::query()->spendable()->distinct()->count('student_schedule_id');

        $dailySpenders = AcornTransaction::query()
            ->where('amount', '<', 0)
            ->where('earned_on', '>=', $weekAgo)
            ->selectRaw('earned_on, count(distinct student_schedule_id) as spenders')
            ->groupBy('earned_on')
            ->pluck('spenders', 'earned_on');

        $chart = [];

        for ($day = 6; $day >= 0; $day--) {
            $chart[] = (int) ($dailySpenders[$today->copy()->subDays($day)->toDateString()] ?? 0);
        }

        return [
            Stat::make('今日花費人數', number_format($spendersSince($todayDate)))
                ->description('今天花費 '.number_format($spentSince($todayDate)).' 顆橡實'),
            Stat::make('近 7 天花費人數', number_format($spendersSince($weekAgo)))
                ->description('共花費 '.number_format($spentSince($weekAgo)).' 顆橡實')
                ->chart($chart),
            Stat::make('持有橡實人數', number_format($holders))
                ->description('目前有未過期橡實的課表'),
        ];
    }
}
