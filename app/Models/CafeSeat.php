<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\CafeSeatKind;
use Database\Factories\CafeSeatFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class CafeSeat extends Model
{
    /** @use HasFactory<CafeSeatFactory> */
    use HasFactory;

    protected $casts = [
        'kind' => CafeSeatKind::class,
        'seat_number' => 'integer',
        'occupied_at' => 'datetime',
        'last_seen_at' => 'datetime',
    ];

    /**
     * Seats are addressed by their public code, never by id.
     */
    public function getRouteKeyName(): string
    {
        return 'code';
    }

    /**
     * @return BelongsTo<StudentSchedule, $this>
     */
    public function schedule(): BelongsTo
    {
        return $this->belongsTo(StudentSchedule::class, 'student_schedule_id');
    }
}
