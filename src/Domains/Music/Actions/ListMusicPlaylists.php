<?php

declare(strict_types=1);

namespace NouTools\Domains\Music\Actions;

use App\Models\MusicPlaylist;
use NouTools\Domains\Music\ViewModels\MusicPlaylistViewModel;

/**
 * Every playlist that has at least one track, oldest first, with its tracks in
 * playback order. Empty playlists are skipped: there is nothing to play.
 */
final readonly class ListMusicPlaylists
{
    /**
     * @return array<int, MusicPlaylistViewModel>
     */
    public function __invoke(): array
    {
        return MusicPlaylist::query()
            ->has('tracks')
            ->with('tracks')
            ->orderBy('id')
            ->get()
            ->map(fn (MusicPlaylist $playlist): MusicPlaylistViewModel => MusicPlaylistViewModel::fromModel($playlist))
            ->all();
    }
}
