<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\MusicPlaylistItemFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class MusicPlaylistItem extends Model
{
    /** @use HasFactory<MusicPlaylistItemFactory> */
    use HasFactory;

    protected $fillable = [
        'music_track_id',
        'position',
    ];

    /**
     * @return BelongsTo<MusicPlaylist, $this>
     */
    public function playlist(): BelongsTo
    {
        return $this->belongsTo(MusicPlaylist::class, 'music_playlist_id');
    }

    /**
     * @return BelongsTo<MusicTrack, $this>
     */
    public function track(): BelongsTo
    {
        return $this->belongsTo(MusicTrack::class, 'music_track_id');
    }
}
