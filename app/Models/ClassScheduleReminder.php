<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ClassScheduleReminderStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class ClassScheduleReminder extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'class_schedule_id',
        'student_schedule_id',
        'status',
        'failure_reason',
        'sent_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => ClassScheduleReminderStatus::class,
            'sent_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<ClassSchedule, $this>
     */
    public function classSchedule(): BelongsTo
    {
        return $this->belongsTo(ClassSchedule::class);
    }

    /**
     * @return BelongsTo<StudentSchedule, $this>
     */
    public function studentSchedule(): BelongsTo
    {
        return $this->belongsTo(StudentSchedule::class);
    }
}
