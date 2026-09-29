<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use Filament\Widgets\ChartWidget;
use Illuminate\Contracts\Support\Htmlable;
use NouTools\Domains\Shared\Actions\ResolvePushDeliveryMetrics;

final class PushDeliveryChart extends ChartWidget
{
    protected static ?int $sort = 40;

    protected ?string $heading = '推播送達（近 7 天）';

    protected function getType(): string
    {
        return 'doughnut';
    }

    public function getDescription(): string|Htmlable|null
    {
        $reasons = app(ResolvePushDeliveryMetrics::class)()['failureReasons'];

        if ($reasons === []) {
            return '沒有失敗紀錄';
        }

        return '失敗原因：'.collect($reasons)
            ->take(3)
            ->map(fn (int $count, string $reason): string => "{$reason} ×{$count}")
            ->implode('、');
    }

    /**
     * @return array<string, mixed>
     */
    protected function getData(): array
    {
        $metrics = app(ResolvePushDeliveryMetrics::class)();

        return [
            'datasets' => [
                [
                    'data' => [$metrics['succeeded'], $metrics['failed']],
                    'backgroundColor' => ['#22c55e', '#ef4444'],
                ],
            ],
            'labels' => ['成功', '失敗'],
        ];
    }
}
