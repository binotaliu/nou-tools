<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Textbook extends Model
{
    use HasFactory;

    protected $fillable = [
        'course_id',
        'term',
        'department',
        'book_title',
        'edition',
        'price_info',
        'reference_url',
    ];

    /**
     * @return BelongsTo<Course, $this>
     */
    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }
}
