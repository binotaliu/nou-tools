<?php

declare(strict_types=1);

namespace App\Filament\Resources\MusicPlaylists\Pages;

use App\Filament\Resources\MusicPlaylists\MusicPlaylistResource;
use Filament\Resources\Pages\CreateRecord;

class CreateMusicPlaylist extends CreateRecord
{
    protected static string $resource = MusicPlaylistResource::class;
}
