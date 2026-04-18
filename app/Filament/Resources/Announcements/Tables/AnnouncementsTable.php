<?php

namespace App\Filament\Resources\Announcements\Tables;

use App\Models\Announcement;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class AnnouncementsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('source_name')
                    ->label('來源')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('category')
                    ->label('分類')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('title')
                    ->label('標題')
                    ->searchable()
                    ->sortable()
                    ->limit(60)
                    ->url(fn ($record): string => $record->url, shouldOpenInNewTab: true),
                TextColumn::make('tags')
                    ->label('標籤')
                    ->badge()
                    ->separator(','),
                TextColumn::make('published_at')
                    ->label('發佈時間')
                    ->dateTime('Y-m-d H:i')
                    ->sortable(),
                TextColumn::make('fetched_at')
                    ->label('截取時間')
                    ->dateTime('Y-m-d H:i')
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('expired_at')
                    ->label('過期時間')
                    ->dateTime('Y-m-d H:i')
                    ->sortable()
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('source_key')
                    ->label('來源')
                    ->options(fn (): array => Announcement::query()
                        ->select(['source_key', 'source_name', 'category'])
                        ->distinct()
                        ->orderBy('source_name')
                        ->get()
                        ->mapWithKeys(fn (Announcement $announcement) => [
                            $announcement->source_key => "{$announcement->source_name} — {$announcement->category}",
                        ])
                        ->all()),
                TernaryFilter::make('expired')
                    ->label('過期狀態')
                    ->nullable()
                    ->trueLabel('已過期')
                    ->falseLabel('未過期')
                    ->queries(
                        true: fn ($query) => $query->whereNotNull('expired_at'),
                        false: fn ($query) => $query->whereNull('expired_at'),
                    ),
            ])
            ->defaultSort('published_at', 'desc');
    }
}
