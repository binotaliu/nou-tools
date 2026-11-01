<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\StickyNoteStatus;
use Database\Factories\CafeStickyNoteFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Prunable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Storage;

final class CafeStickyNote extends Model
{
    /** @use HasFactory<CafeStickyNoteFactory> */
    use HasFactory;

    use Prunable;

    /**
     * Drawings live on a private disk and are only ever served through
     * `cafe.notes.drawing`, never by URL.
     */
    public const DRAWING_DISK = 'cafe_sticky_notes';

    protected $casts = [
        'status' => StickyNoteStatus::class,
        'ai_verdict' => 'array',
        'x' => 'float',
        'y' => 'float',
        'rotation' => 'integer',
        'report_count' => 'integer',
        'published_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        self::deleting(function (CafeStickyNote $note): void {
            if ($note->drawing_path !== null) {
                Storage::disk(self::DRAWING_DISK)->delete($note->drawing_path);
            }
        });
    }

    /**
     * On the wall right now: published and not yet past its expiry.
     *
     * @param  Builder<CafeStickyNote>  $query
     * @return Builder<CafeStickyNote>
     */
    public function scopeOnWall(Builder $query): Builder
    {
        return $query->where('status', StickyNoteStatus::Published)
            ->where('expires_at', '>', Date::now());
    }

    /**
     * Notes a week past their expiry are removed with their drawings.
     *
     * @return Builder<CafeStickyNote>
     */
    public function prunable(): Builder
    {
        return self::query()
            ->whereNotNull('expires_at')
            ->where('expires_at', '<', Date::now()->subWeek());
    }

    /**
     * @return BelongsTo<StudentSchedule, $this>
     */
    public function schedule(): BelongsTo
    {
        return $this->belongsTo(StudentSchedule::class, 'student_schedule_id');
    }

    /**
     * @return BelongsTo<AcornTransaction, $this>
     */
    public function acornTransaction(): BelongsTo
    {
        return $this->belongsTo(AcornTransaction::class);
    }

    /**
     * @return HasMany<CafeStickyNoteReport, $this>
     */
    public function reports(): HasMany
    {
        return $this->hasMany(CafeStickyNoteReport::class);
    }
}
