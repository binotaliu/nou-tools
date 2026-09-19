<?php

declare(strict_types=1);

namespace App\Filament\Resources\MusicPlaylists\Pages;

use App\Filament\Resources\MusicPlaylists\MusicPlaylistResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditMusicPlaylist extends EditRecord
{
    protected static string $resource = MusicPlaylistResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->modalDescription('只會刪除播放清單與封面圖片，清單內的曲目不受影響。'),
        ];
    }
}
