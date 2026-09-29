<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use NouTools\Domains\Shared\Actions\ResolveStudyRoomMetrics;

final class StudyRoomStats extends StatsOverviewWidget
{
    protected static ?int $sort = 20;

    protected ?string $heading = '自習室';

    /**
     * @return array<int, Stat>
     */
    protected function getStats(): array
    {
        $metrics = app(ResolveStudyRoomMetrics::class)();

        return [
            Stat::make('目前座位', $metrics['occupied'].' / '.$metrics['capacity'])
                ->description('使用中 / 全部座位'),
            Stat::make('本週專注時數', number_format($metrics['focusHoursThisWeek'], 1))
                ->description('最近 7 天累計（小時）')
                ->chart(array_slice(array_values($metrics['dailyFocusHours']), -7)),
            Stat::make('今日場次', number_format($metrics['sessionsToday'])),
            Stat::make('完成率', $metrics['completionRate'].'%')
                ->description('最近 7 天完成計畫的場次'),
        ];
    }
}
