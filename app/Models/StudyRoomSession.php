<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\StudyActivityVerb;
use App\Enums\StudyTimerMode;
use Database\Factories\StudyRoomSessionFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class StudyRoomSession extends Model
{
    /** @use HasFactory<StudyRoomSessionFactory> */
    use HasFactory;

    protected $fillable = [
        'student_schedule_id',
        'subject_course_id',
        'subject_label',
        'activity_verb',
        'timer_mode',
        'started_at',
        'ended_at',
        'focus_seconds',
        'overtime_seconds',
        'was_completed',
    ];

    protected $casts = [
        'activity_verb' => StudyActivityVerb::class,
        'timer_mode' => StudyTimerMode::class,
        'started_at' => 'datetime',
        'ended_at' => 'datetime',
        'was_completed' => 'boolean',
    ];

    /**
     * Drops 面授 sessions; a null verb counts as ordinary study.
     *
     * @param  Builder<StudyRoomSession>  $query
     * @return Builder<StudyRoomSession>
     */
    public function scopeExcludingInPersonClass(Builder $query): Builder
    {
        return $query->where(function (Builder $query): void {
            $query->whereNull('activity_verb')
                ->orWhere('activity_verb', '!=', StudyActivityVerb::InPersonClass->value);
        });
    }

    /**
     * @return BelongsTo<StudentSchedule, $this>
     */
    public function schedule(): BelongsTo
    {
        return $this->belongsTo(StudentSchedule::class, 'student_schedule_id');
    }

    /**
     * @return BelongsTo<Course, $this>
     */
    public function subjectCourse(): BelongsTo
    {
        return $this->belongsTo(Course::class, 'subject_course_id');
    }
}
