<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use NouTools\Domains\StudyRoom\Actions\ListMusicPlaylists;
use NouTools\Domains\StudyRoom\ViewModels\MusicPlaylistListViewModel;
use NouTools\Domains\StudyRoom\ViewModels\MusicPlaylistViewModel;
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
