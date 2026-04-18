<?php

namespace App\Models;

use Database\Factories\AnnouncementFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Announcement extends Model
{
    /** @use HasFactory<AnnouncementFactory> */
    use HasFactory;

    protected $fillable = [
        'source_key',
        'source_name',
        'category',
        'source_id',
        'title',
        'url',
        'tags',
        'published_at',
        'fetched_at',
        'expired_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'tags' => 'array',
            'published_at' => 'datetime',
            'fetched_at' => 'datetime',
            'expired_at' => 'datetime',
        ];
    }
}
