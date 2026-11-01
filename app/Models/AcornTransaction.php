<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\AcornTransactionKind;
use Database\Factories\AcornTransactionFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Facades\Date;

final class AcornTransaction extends Model
{
    /** @use HasFactory<AcornTransactionFactory> */
    use HasFactory;

    protected $casts = [
        'kind' => AcornTransactionKind::class,
        'amount' => 'integer',
        'remaining' => 'integer',
        'expires_at' => 'datetime',
        'meta' => 'array',
    ];

    /**
     * Credits that can still be spent: positive, unspent and not expired.
     *
     * @param  Builder<AcornTransaction>  $query
     * @return Builder<AcornTransaction>
     */
    public function scopeSpendable(Builder $query): Builder
    {
        return $query->where('amount', '>', 0)
            ->where('remaining', '>', 0)
            ->where('expires_at', '>', Date::now());
    }

    /**
     * @return BelongsTo<StudentSchedule, $this>
     */
    public function schedule(): BelongsTo
    {
        return $this->belongsTo(StudentSchedule::class, 'student_schedule_id');
    }

    /**
     * @return MorphTo<Model, $this>
     */
    public function source(): MorphTo
    {
        return $this->morphTo();
    }
}
