<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ChangelogPostStatus;
use Database\Factories\ChangelogPostFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class ChangelogPost extends Model
{
    /** @use HasFactory<ChangelogPostFactory> */
    use HasFactory;

    protected $fillable = [
        'slug',
        'title',
        'body',
    ];

    protected $attributes = [
        'status' => 'draft',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => ChangelogPostStatus::class,
            'published_at' => 'datetime',
        ];
    }

    /**
     * @param  Builder<ChangelogPost>  $query
     */
    public function scopePublished(Builder $query): void
    {
        $query->where('status', ChangelogPostStatus::Published);
    }

    public function isPublished(): bool
    {
        return $this->status === ChangelogPostStatus::Published;
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }
}
