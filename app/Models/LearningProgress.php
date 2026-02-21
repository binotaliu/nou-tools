<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LearningProgress extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_schedule_id',
        'term',
        'progress',
        'notes',
    ];

    protected $table = 'learning_progresses';

    protected $casts = [
        'progress' => 'json',
        'notes' => 'json',
    ];

    public function studentSchedule(): BelongsTo
    {
        return $this->belongsTo(StudentSchedule::class);
    }
}
