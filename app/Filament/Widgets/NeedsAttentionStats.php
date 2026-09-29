<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Filament\Resources\DiscountStores\DiscountStoreResource;
use App\Filament\Resources\NewsletterIssues\NewsletterIssueResource;
use App\Models\User;
use Filament\Facades\Filament;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use NouTools\Domains\Shared\Actions\ResolveModerationQueueCounts;

final class NeedsAttentionStats extends StatsOverviewWidget
{
    protected static ?int $sort = 5;

    protected ?string $heading = '待處理事項';

    public static function canView(): bool
    {
        $user = Filament::auth()->user();

        return $user instanceof User && $user->isAdmin();
    }

    /**
     * @return array<int, Stat>
     */
    protected function getStats(): array
    {
        $counts = app(ResolveModerationQueueCounts::class)();

        return [
            Stat::make('待審核店家', $counts['pendingStores'])
                ->color($counts['pendingStores'] > 0 ? 'warning' : 'success')
                ->url(DiscountStoreResource::getUrl('index', ['filters' => ['status' => ['value' => 'pending']]])),
            Stat::make('待審核留言', $counts['unapprovedComments'])
                ->color($counts['unapprovedComments'] > 0 ? 'warning' : 'success'),
            Stat::make('店家失效回報', $counts['invalidReports'])
                ->description('回報「已失效」的累計次數')
                ->color($counts['invalidReports'] > 0 ? 'warning' : 'success'),
            Stat::make('待發布電子報', $counts['newsletterAwaitingPublish'])
                ->color($counts['newsletterAwaitingPublish'] > 0 ? 'warning' : 'success')
                ->url(NewsletterIssueResource::getUrl('index')),
        ];
    }
}
