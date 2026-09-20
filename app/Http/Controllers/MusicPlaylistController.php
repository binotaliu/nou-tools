<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use NouTools\Domains\Music\Actions\ListMusicPlaylists;
use NouTools\Domains\Music\ViewModels\MusicPlaylistListViewModel;
use NouTools\Domains\Music\ViewModels\MusicPlaylistViewModel;
use Spatie\LaravelData\DataCollection;

final class MusicPlaylistController extends Controller
{
    public function __invoke(ListMusicPlaylists $listMusicPlaylists): JsonResponse
    {
        return response()->json(new MusicPlaylistListViewModel(
            playlists: MusicPlaylistViewModel::collect($listMusicPlaylists(), DataCollection::class),
        ));
    }
}
