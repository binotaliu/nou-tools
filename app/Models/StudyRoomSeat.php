<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\StudyActivityVerb;
use App\Enums\StudySeatKind;
use App\Enums\StudyTimerMode;
use App\Enums\StudyTimerPhase;
use Database\Factories\StudyRoomSeatFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class StudyRoomSeat extends Model
{
    /** @use HasFactory<StudyRoomSeatFactory> */
    use HasFactory;

    protected $fillable = [
        'floor',
        'kind',
        'group_code',
        'seat_number',
        'code',
        'label',
        'student_schedule_id',
        'occupied_at',
        'last_seen_at',
        'activity_verb',
        'subject_course_id',
        'subject_label',
        'timer_mode',
        'timer_phase',
        'timer_round',
        'timer_started_at',
        'activity_started_at',
        'timer_ends_at',
    ];

    protected $casts = [
        'kind' => StudySeatKind::class,
        'activity_verb' => StudyActivityVerb::class,
        'timer_mode' => StudyTimerMode::class,
        'timer_phase' => StudyTimerPhase::class,
        'timer_round' => 'integer',
        'occupied_at' => 'datetime',
        'last_seen_at' => 'datetime',
        'timer_started_at' => 'datetime',
        'activity_started_at' => 'datetime',
        'timer_ends_at' => 'datetime',
    ];

    /**
     * Seats are addressed by their public code, never by id.
     */
    public function getRouteKeyName(): string
    {
        return 'code';
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
