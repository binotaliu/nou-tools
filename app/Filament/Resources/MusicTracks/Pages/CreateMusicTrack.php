<?php

declare(strict_types=1);

namespace App\Filament\Resources\MusicTracks\Pages;

use App\Filament\Resources\MusicTracks\MusicTrackResource;
use App\Models\MusicTrack;
use Filament\Resources\Pages\CreateRecord;

class CreateMusicTrack extends CreateRecord
{
    protected static string $resource = MusicTrackResource::class;

    /**
     * Prefill the album-level details when arriving from the duplicate action
     * (`?duplicate={id}`); title, audio files and duration are per-track.
     */
    protected function fillForm(): void
    {
        $this->callHook('beforeFill');

        $source = MusicTrack::query()->find(request()->query('duplicate'));

        $this->form->fill($source?->only(['author', 'license', 'license_url', 'source_url']));

        $this->callHook('afterFill');
    }
}
