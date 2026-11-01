<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\CafeOrderFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class CafeOrder extends Model
{
    /** @use HasFactory<CafeOrderFactory> */
    use HasFactory;

    protected $casts = [
        'to_go' => 'boolean',
        'price_paid' => 'integer',
        'ready_at' => 'datetime',
        'sips_taken' => 'integer',
        'expires_at' => 'datetime',
    ];

    /**
     * @return BelongsTo<StudentSchedule, $this>
     */
    public function schedule(): BelongsTo
    {
        return $this->belongsTo(StudentSchedule::class, 'student_schedule_id');
    }

    /**
     * @return BelongsTo<CafeMenuItem, $this>
     */
    public function menuItem(): BelongsTo
    {
        return $this->belongsTo(CafeMenuItem::class, 'cafe_menu_item_id');
    }
}
