<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use NouTools\Domains\Shared\Actions\ResolveScheduleMetrics;

final class ScheduleOverviewStats extends StatsOverviewWidget
{
    protected static ?int $sort = 10;

    protected ?string $heading = '課表使用概況';

    /**
     * @return array<int, Stat>
     */
    protected function getStats(): array
    {
        $metrics = app(ResolveScheduleMetrics::class)();

        return [
            Stat::make('課表總數', number_format($metrics['total']))
                ->chart(array_values($metrics['dailyNew']))
                ->description('近 30 天每日新增'),
            Stat::make('本週新增', number_format($metrics['newThisWeek']))
                ->description('最近 7 天建立的課表'),
            Stat::make('上課提醒開啟率', $metrics['reminderRate'].'%')
                ->description('已開啟上課提醒的課表'),
            Stat::make('行事曆同步率', $metrics['calendarSyncRate'].'%')
                ->description('曾同步行事曆的課表'),
            Stat::make('自習室暱稱', number_format($metrics['profiles']))
                ->description('已建立自習室個人檔案'),
        ];
    }
}
