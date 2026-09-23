<?php

declare(strict_types=1);

namespace App\Filament\Resources\ChangelogPosts\Tables;

use App\Enums\ChangelogPostStatus;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class ChangelogPostsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')
                    ->label('標題')
                    ->searchable(),
                TextColumn::make('status')
                    ->label('狀態')
                    ->badge()
                    ->formatStateUsing(fn (ChangelogPostStatus $state): string => $state->label())
                    ->color(fn (ChangelogPostStatus $state): string => $state->color()),
                TextColumn::make('published_at')
                    ->label('發布時間')
                    ->dateTime('Y-m-d H:i', timezone: 'Asia/Taipei')
                    ->placeholder('—')
                    ->sortable(),
            ])
            ->defaultSort('published_at', 'desc')
            ->filters([
                SelectFilter::make('status')
                    ->label('狀態')
                    ->options(ChangelogPostStatus::getLabels()),
            ])
            ->recordActions([
                EditAction::make(),
            ]);
    }
}
