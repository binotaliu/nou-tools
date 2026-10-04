<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\CourseClassType;
use Database\Factories\CourseClassFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class CourseClass extends Model
{
    /** @use HasFactory<CourseClassFactory> */
    use HasFactory;

    protected $fillable = [
        'course_id',
        'program_id',
        'code',
        'type',
        'start_time',
        'end_time',
        'teacher_name',
        'link',
        'backup_classroom_url',
        'is_tentative',
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
            'is_tentative' => 'bool',
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
     * @return BelongsTo<Program, $this>
     */
    public function program(): BelongsTo
    {
        return $this->belongsTo(Program::class);
    }

    /**
     * @return HasMany<ClassSchedule, $this>
     */
    public function schedules(): HasMany
    {
        return $this->hasMany(ClassSchedule::class, 'class_id');
    }

    /**
     * Classes open to 一般生, i.e. not tied to a 專班.
     *
     * @param  Builder<CourseClass>  $query
     * @return Builder<CourseClass>
     */
    public function scopeGeneral(Builder $query): Builder
    {
        return $query->whereNull('program_id');
    }

    /**
     * @param  Builder<CourseClass>  $query
     * @return Builder<CourseClass>
     */
    public function scopeOfficial(Builder $query): Builder
    {
        return $query->where('is_tentative', false);
    }
}
