<?php

declare(strict_types=1);

namespace NouTools\Domains\Music\ViewModels;

use App\Models\MusicPlaylist;
use Illuminate\Support\Facades\Storage;
use Spatie\LaravelData\Attributes\DataCollectionOf;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\DataCollection;

final class MusicPlaylistViewModel extends Data
{
    public function __construct(
        public int $id,
        public string $title,
        public ?string $description,
        public ?string $coverImageUrl,
        public int $trackCount,
        public int $totalDurationSeconds,
        #[DataCollectionOf(MusicTrackViewModel::class)]
        public DataCollection $tracks,
    ) {}

    /**
     * Expects `tracks` to be loaded, in playback order.
     */
    public static function fromModel(MusicPlaylist $playlist): self
    {
        return new self(
            id: $playlist->id,
            title: $playlist->title,
            description: $playlist->description,
            coverImageUrl: $playlist->cover_image !== null ? Storage::disk(MusicPlaylist::COVER_DISK)->url($playlist->cover_image) : null,
            trackCount: $playlist->tracks->count(),
            totalDurationSeconds: (int) $playlist->tracks->sum('duration_seconds'),
            tracks: MusicTrackViewModel::collect(
                $playlist->tracks->map(fn ($track): MusicTrackViewModel => MusicTrackViewModel::fromModel($track))->all(),
                DataCollection::class,
            ),
        );
    }
}
