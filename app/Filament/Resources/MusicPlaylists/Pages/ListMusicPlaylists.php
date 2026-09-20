<?php

declare(strict_types=1);

namespace App\Filament\Resources\MusicPlaylists\Pages;

use App\Filament\Resources\MusicPlaylists\MusicPlaylistResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListMusicPlaylists extends ListRecords
{
    protected static string $resource = MusicPlaylistResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
