<?php

namespace App\Models;

use App\Enums\CourseClassType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CourseClass extends Model
{
    /** @use HasFactory<\Database\Factories\CourseClassFactory> */
    use HasFactory;

    protected $fillable = [
        'course_id',
        'code',
        'type',
        'start_time',
        'end_time',
        'teacher_name',
        'link',
    ];

    /**
     * Ensure class `code` is stored in uppercase.
     */
    public function setCodeAttribute(?string $value): void
    {
        $this->attributes['code'] = $value === null ? null : strtoupper($value);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => CourseClassType::class,
        ];
    }

    /**
     * @return BelongsTo<Course, $this>
     */
    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    /**
     * @return HasMany<ClassSchedule, $this>
     */
    public function schedules(): HasMany
    {
        return $this->hasMany(ClassSchedule::class, 'class_id');
    }
}
