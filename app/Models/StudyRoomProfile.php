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
        'nickname_changed_at',
        'nickname_reset_at',
        'nickname_reset_by',
    ];

    protected $casts = [
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
