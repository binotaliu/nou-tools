<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Enums\NewsletterIssueStatus;
use App\Filament\Resources\NewsletterIssues\NewsletterIssueResource;
use App\Models\NewsletterIssue;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;

final class LatestNewsletterIssues extends TableWidget
{
    protected static ?int $sort = 50;

    protected int|string|array $columnSpan = 'full';

    protected static ?string $heading = '近期電子報互動';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                NewsletterIssue::query()
                    ->where('status', NewsletterIssueStatus::Published)
                    ->withCount('reactions')
                    ->latest('published_at')
            )
            ->paginated(false)
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->limit(5))
            ->columns([
                TextColumn::make('title')->label('標題'),
                TextColumn::make('published_at')->label('發布時間')->dateTime('Y-m-d'),
                TextColumn::make('view_count')->label('瀏覽數')->numeric(),
                TextColumn::make('reactions_count')->label('回應數')->numeric(),
            ])
            ->recordUrl(fn (NewsletterIssue $record): string => NewsletterIssueResource::getUrl('edit', ['record' => $record]));
    }
}
