<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ArcadeGame;
use Database\Factories\ArcadePlayFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Prunable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Date;

final class ArcadePlay extends Model
{
    /** @use HasFactory<ArcadePlayFactory> */
    use HasFactory;

    use Prunable;

    protected $casts = [
        'game' => ArcadeGame::class,
        'result' => 'array',
    ];

    /**
     * Plays are only meaningful on the day they were paid for; keep a quarter
     * of history and drop the rest.
     *
     * @return Builder<ArcadePlay>
     */
    public function prunable(): Builder
    {
        return self::query()->where('created_at', '<', Date::now()->subDays(90));
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
}
