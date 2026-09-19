<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\MusicPlaylist;
use App\Models\MusicPlaylistItem;
use App\Models\MusicTrack;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MusicPlaylistItem>
 */
final class MusicPlaylistItemFactory extends Factory
{
    protected $model = MusicPlaylistItem::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'music_playlist_id' => MusicPlaylist::factory(),
            'music_track_id' => MusicTrack::factory(),
            'position' => 0,
        ];
    }
}
