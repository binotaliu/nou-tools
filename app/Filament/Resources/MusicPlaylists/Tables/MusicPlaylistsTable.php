<?php

declare(strict_types=1);

namespace App\Filament\Resources\MusicPlaylists\Tables;

use App\Filament\Resources\MusicTracks\Tables\MusicTracksTable;
use App\Models\MusicPlaylist;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class MusicPlaylistsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('cover_image')
                    ->label('封面')
                    ->disk(MusicPlaylist::COVER_DISK)
                    ->visibility('public')
                    ->square(),
                TextColumn::make('title')
                    ->label('標題')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('items_count')
                    ->label('曲目數')
                    ->counts('items'),
                TextColumn::make('tracks_sum_duration_seconds')
                    ->label('總長度')
                    ->sum('tracks', 'duration_seconds')
                    ->formatStateUsing(fn (?int $state): string => MusicTracksTable::formatDuration((int) $state)),
            ])
            ->defaultSort('created_at', 'desc')
            ->recordActions([
                EditAction::make(),
            ]);
    }
}
