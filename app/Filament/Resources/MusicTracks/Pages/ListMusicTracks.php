<?php

declare(strict_types=1);

namespace App\Filament\Resources\MusicTracks\Pages;

use App\Filament\Resources\MusicTracks\MusicTrackResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListMusicTracks extends ListRecords
{
    protected static string $resource = MusicTrackResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
