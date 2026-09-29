<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use Filament\Widgets\ChartWidget;
use NouTools\Domains\Shared\Actions\ResolveStudyRoomMetrics;

final class StudyFocusChart extends ChartWidget
{
    protected static ?int $sort = 30;

    protected ?string $heading = '每日專注時數（近 14 天）';

    protected function getType(): string
    {
        return 'bar';
    }

    /**
     * @return array<string, mixed>
     */
    protected function getData(): array
    {
        $daily = app(ResolveStudyRoomMetrics::class)()['dailyFocusHours'];

        return [
            'datasets' => [
                ['label' => '專注時數', 'data' => array_values($daily)],
            ],
            'labels' => array_map(fn (string $day): string => substr($day, 5), array_keys($daily)),
        ];
    }
}
