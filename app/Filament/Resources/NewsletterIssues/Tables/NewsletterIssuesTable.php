<?php

declare(strict_types=1);

namespace App\Filament\Resources\NewsletterIssues\Tables;

use App\Enums\NewsletterIssueStatus;
use App\Models\NewsletterIssue;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class NewsletterIssuesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('issue_key')
                    ->label('期號')
                    ->searchable(),
                TextColumn::make('publishes_on')
                    ->label('發刊日')
                    ->date('Y-m-d')
                    ->sortable(),
                TextColumn::make('title')
                    ->label('標題')
                    ->state(fn (NewsletterIssue $record): string => $record->displayTitle()),
                TextColumn::make('status')
                    ->label('狀態')
                    ->badge()
                    ->formatStateUsing(fn (NewsletterIssueStatus $state): string => $state->label())
                    ->color(fn (NewsletterIssueStatus $state): string => $state->color()),
                TextColumn::make('covers_from')
                    ->label('公告範圍')
                    ->state(fn (NewsletterIssue $record): string => "{$record->covers_from->toDateString()} ～ {$record->covers_to->toDateString()}")
                    ->toggleable(),
                TextColumn::make('items_count')
                    ->label('消息數')
                    ->counts('items'),
                TextColumn::make('published_at')
                    ->label('發布時間')
                    ->dateTime('Y-m-d H:i', timezone: 'Asia/Taipei')
                    ->placeholder('—')
                    ->toggleable(),
            ])
            ->defaultSort('publishes_on', 'desc')
            ->filters([
                SelectFilter::make('status')
                    ->label('狀態')
                    ->options(NewsletterIssueStatus::getLabels()),
            ])
            ->recordActions([
                EditAction::make(),
            ]);
    }
}
