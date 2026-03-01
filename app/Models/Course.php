<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Course extends Model
{
    /** @use HasFactory<\Database\Factories\CourseFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'term',
        'description_url',
        'credit_type',
        'credits',
        'department',
        'in_person_class_type',
        'media',
        'multimedia_url',
        'nature',
        'midterm_date',
        'final_date',
        'exam_time_start',
        'exam_time_end',
    ];

    /**
     * @return HasMany<CourseClass, $this>
     */
    public function classes(): HasMany
    {
        return $this->hasMany(CourseClass::class);
    }

    public function textbook(): HasOne
    {
        return $this->hasOne(Textbook::class);
    }
}
