<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use Filament\Widgets\ChartWidget;
use NouTools\Domains\Shared\Actions\ResolveScheduleMetrics;

final class NewSchedulesChart extends ChartWidget
{
    protected static ?int $sort = 15;

    protected ?string $heading = '每日新增課表（近 30 天）';

    protected function getType(): string
    {
        return 'line';
    }

    /**
     * @return array<string, mixed>
     */
    protected function getData(): array
    {
        $daily = app(ResolveScheduleMetrics::class)()['dailyNew'];

        return [
            'datasets' => [
                ['label' => '新增課表', 'data' => array_values($daily)],
            ],
            'labels' => array_map(fn (string $day): string => substr($day, 5), array_keys($daily)),
        ];
    }
}
