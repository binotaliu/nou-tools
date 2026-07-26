<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class PreviousExam extends Model
{
    use HasFactory;

    protected $fillable = [
        'course_name',
        'course_no',
        'term',
        'midterm_reference_primary',
        'midterm_reference_secondary',
        'final_reference_primary',
        'final_reference_secondary',
    ];

    /**
     * @return BelongsTo<Course, $this>
     */
    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class, 'course_name', 'name');
    }
}
