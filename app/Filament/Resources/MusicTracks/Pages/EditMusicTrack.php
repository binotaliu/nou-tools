<?php

declare(strict_types=1);

namespace App\Filament\Resources\MusicTracks\Pages;

use App\Filament\Resources\MusicTracks\Actions\DuplicateMusicTrackAction;
use App\Filament\Resources\MusicTracks\MusicTrackResource;
use App\Models\MusicTrack;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

/**
 * @property MusicTrack $record
 */
class EditMusicTrack extends EditRecord
{
    protected static string $resource = MusicTrackResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DuplicateMusicTrackAction::make(),
            DeleteAction::make()
                ->modalDescription(fn (MusicTrack $record): string => $record->playlistItems()->exists()
                    ? '這首曲目正在播放清單中使用，刪除後會一併從所有播放清單移除，並刪除 mp3 / ogg 檔案。'
                    : '會一併刪除 mp3 / ogg 檔案。'),
        ];
    }
}
