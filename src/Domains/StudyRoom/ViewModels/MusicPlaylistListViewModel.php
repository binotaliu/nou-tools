<?php

declare(strict_types=1);

namespace NouTools\Domains\StudyRoom\ViewModels;

use Spatie\LaravelData\Attributes\DataCollectionOf;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\DataCollection;

final class MusicPlaylistListViewModel extends Data
{
    public function __construct(
        #[DataCollectionOf(MusicPlaylistViewModel::class)]
        public DataCollection $playlists,
    ) {}
}
