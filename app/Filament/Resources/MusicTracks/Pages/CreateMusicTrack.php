<?php

declare(strict_types=1);

namespace App\Filament\Resources\MusicTracks\Pages;

use App\Filament\Resources\MusicTracks\MusicTrackResource;
use Filament\Resources\Pages\CreateRecord;

class CreateMusicTrack extends CreateRecord
{
    protected static string $resource = MusicTrackResource::class;
}
