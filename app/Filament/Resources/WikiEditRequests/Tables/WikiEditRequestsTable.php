<?php

declare(strict_types=1);

namespace App\Filament\Resources\WikiEditRequests\Tables;

use App\Enums\WikiEditRequestStatus;
use App\Models\WikiEditRequest;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class WikiEditRequestsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('created_at')
                    ->label('提出時間')
                    ->dateTime('Y-m-d H:i', timezone: 'Asia/Taipei')
                    ->sortable(),
                TextColumn::make('status')
                    ->label('狀態')
                    ->badge()
                    ->formatStateUsing(fn (WikiEditRequestStatus $state): string => $state->label())
                    ->color(fn (WikiEditRequestStatus $state): string => $state->color()),
                TextColumn::make('target')
                    ->label('條目')
                    ->state(fn (WikiEditRequest $record): string => $record->page?->slug ?? '（新條目）'.($record->draft_title ?? $record->requested_title ?? '')),
                TextColumn::make('draft_edit_summary')
                    ->label('編輯摘要')
                    ->placeholder('—')
                    ->limit(50),
                TextColumn::make('risk')
                    ->label('風險')
                    ->badge()
                    ->state(fn (WikiEditRequest $record): ?string => $record->risk()?->label())
                    ->color(fn (WikiEditRequest $record): string => $record->risk()?->color() ?? 'gray')
                    ->placeholder('—'),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('status')
                    ->label('狀態')
                    ->options(WikiEditRequestStatus::getLabels())
                    ->default(WikiEditRequestStatus::PendingReview->value),
            ])
            ->recordActions([
                ViewAction::make(),
            ]);
    }
}
