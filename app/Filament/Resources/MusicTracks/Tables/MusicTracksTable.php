<?php

declare(strict_types=1);

namespace App\Filament\Resources\MusicTracks\Tables;

use App\Filament\Resources\MusicTracks\Actions\DuplicateMusicTrackAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class MusicTracksTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')
                    ->label('曲名')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('author')
                    ->label('作者')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('license')
                    ->label('授權')
                    ->badge()
                    ->color('gray')
                    ->searchable(),
                TextColumn::make('duration_seconds')
                    ->label('長度')
                    ->formatStateUsing(fn (int $state): string => self::formatDuration($state))
                    ->sortable(),
                TextColumn::make('playlists_count')
                    ->label('播放清單數')
                    ->counts('playlists'),
            ])
            ->defaultSort('created_at', 'desc')
            ->recordActions([
                EditAction::make(),
                DuplicateMusicTrackAction::make(),
            ]);
    }

    public static function formatDuration(int $seconds): string
    {
        return gmdate($seconds >= 3600 ? 'G:i:s' : 'i:s', $seconds);
    }
}
