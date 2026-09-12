<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\StudyRoomProfileFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class StudyRoomProfile extends Model
{
    /** @use HasFactory<StudyRoomProfileFactory> */
    use HasFactory;

    protected $fillable = [
        'student_schedule_id',
        'nickname',
        'emoji',
        'pomodoro_focus_minutes',
        'pomodoro_short_break_minutes',
        'pomodoro_long_break_minutes',
        'pomodoro_rounds_per_cycle',
        'play_sound_on_timer_end',
        'nickname_changed_at',
        'nickname_reset_at',
        'nickname_reset_by',
    ];

    protected $casts = [
        'pomodoro_focus_minutes' => 'integer',
        'pomodoro_short_break_minutes' => 'integer',
        'pomodoro_long_break_minutes' => 'integer',
        'pomodoro_rounds_per_cycle' => 'integer',
        'play_sound_on_timer_end' => 'boolean',
        'nickname_changed_at' => 'datetime',
        'nickname_reset_at' => 'datetime',
    ];

    /**
     * @return BelongsTo<StudentSchedule, $this>
     */
    public function schedule(): BelongsTo
    {
        return $this->belongsTo(StudentSchedule::class, 'student_schedule_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function resetBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'nickname_reset_by');
    }
}
