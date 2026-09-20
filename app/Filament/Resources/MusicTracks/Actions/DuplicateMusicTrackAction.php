<?php

declare(strict_types=1);

namespace App\Filament\Resources\MusicTracks\Actions;

use App\Filament\Resources\MusicTracks\MusicTrackResource;
use App\Models\MusicTrack;
use Filament\Actions\Action;
use Filament\Support\Icons\Heroicon;

/**
 * Opens the create page prefilled with a track's shared details (author and
 * license info) so tracks from the same album can be added quickly. It does not
 * replicate the record: the audio files belong to the original track.
 */
class DuplicateMusicTrackAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'duplicate';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this
            ->label('複製')
            ->icon(Heroicon::OutlinedDocumentDuplicate)
            ->url(fn (MusicTrack $record): string => MusicTrackResource::getUrl('create', ['duplicate' => $record->getKey()]));
    }
}
