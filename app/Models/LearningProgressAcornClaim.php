<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\LearningProgressAcornClaimFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class LearningProgressAcornClaim extends Model
{
    /** @use HasFactory<LearningProgressAcornClaimFactory> */
    use HasFactory;

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
    public function transaction(): BelongsTo
    {
        return $this->belongsTo(AcornTransaction::class, 'acorn_transaction_id');
    }
}
