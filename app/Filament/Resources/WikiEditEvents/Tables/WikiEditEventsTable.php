<?php

declare(strict_types=1);

namespace App\Filament\Resources\WikiEditEvents\Tables;

use App\Enums\WikiEditEventType;
use App\Filament\Resources\WikiEditRequests\WikiEditRequestResource;
use App\Models\WikiEditEvent;
use Filament\Actions\Action;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class WikiEditEventsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('created_at')
                    ->label('時間')
                    ->dateTime('Y-m-d H:i:s', timezone: 'Asia/Taipei')
                    ->sortable(),
                TextColumn::make('event')
                    ->label('事件')
                    ->badge()
                    ->formatStateUsing(fn (WikiEditEventType $state): string => $state->label())
                    ->color(fn (WikiEditEventType $state): string => $state->color()),
                TextColumn::make('page')
                    ->label('條目')
                    ->state(fn (WikiEditEvent $record): string => $record->page_title ?? $record->page_slug ?? '（新條目）')
                    ->description(fn (WikiEditEvent $record): ?string => $record->page_slug),
                TextColumn::make('instruction')
                    ->label('修改說明')
                    ->limit(60)
                    ->tooltip(fn (WikiEditEvent $record): ?string => $record->instruction)
                    ->searchable(),
                TextColumn::make('detail')
                    ->label('原因／備註')
                    ->placeholder('—')
                    ->limit(60)
                    ->tooltip(fn (WikiEditEvent $record): ?string => $record->detail)
                    ->searchable(),
                TextColumn::make('actor')
                    ->label('操作者')
                    ->state(fn (WikiEditEvent $record): string => $record->user?->name
                        ?? ($record->student_schedule_id === null ? '系統' : '課表 #'.$record->student_schedule_id))
                    ->description(fn (WikiEditEvent $record): ?string => $record->ip_hash === null ? null : 'IP '.substr($record->ip_hash, 0, 8)),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('event')
                    ->label('事件')
                    ->options(WikiEditEventType::getLabels())
                    ->multiple(),
            ])
            ->recordActions([
                Action::make('request')
                    ->label('檢視請求')
                    ->icon(Heroicon::OutlinedEye)
                    ->url(fn (WikiEditEvent $record): ?string => $record->wiki_edit_request_id === null
                        ? null
                        : WikiEditRequestResource::getUrl('view', ['record' => $record->wiki_edit_request_id]))
                    ->visible(fn (WikiEditEvent $record): bool => $record->wiki_edit_request_id !== null),
            ]);
    }
}
