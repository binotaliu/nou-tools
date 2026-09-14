<?php

declare(strict_types=1);

namespace NouTools\Domains\Schedules\Actions;

use App\Models\ClassSchedule;
use App\Models\ClassScheduleReminder;
use App\Models\StudentSchedule;
use App\Notifications\ClassStartingSoon;
use Carbon\Carbon;
use Illuminate\Database\UniqueConstraintViolationException;

final class DispatchClassStartingReminders
{
    public function __invoke(): int
    {
        $now = now('Asia/Taipei');
        $windowStart = $now->copy()->addMinutes(9);
        $windowEnd = $now->copy()->addMinutes(11);

        $today = today('Asia/Taipei');
        $tomorrow = $today->copy()->addDay();

        $classSchedules = ClassSchedule::query()
            ->whereHas('courseClass', fn ($query) => $query->where('link', '!=', ''))
            ->where(fn ($query) => $query->whereDate('date', $today)->orWhereDate('date', $tomorrow))
            ->with('courseClass.course')
            ->get();

        $sentCount = 0;

        foreach ($classSchedules as $classSchedule) {
            $startsAt = Carbon::parse(
                $classSchedule->date->format('Y-m-d').' '.($classSchedule->start_time ?? $classSchedule->courseClass->start_time),
                'Asia/Taipei',
            );

            if ($startsAt->lt($windowStart) || $startsAt->gt($windowEnd)) {
                continue;
            }

            $subscribedSchedules = StudentSchedule::query()
                ->whereHas('items', fn ($query) => $query->where('course_class_id', $classSchedule->class_id))
                ->whereHas('pushSubscriptions')
                ->get();

            foreach ($subscribedSchedules as $studentSchedule) {
                if (! $this->markAsSent($classSchedule, $studentSchedule)) {
                    continue;
                }

                $studentSchedule->notify(new ClassStartingSoon($classSchedule));
                $sentCount++;
            }
        }

        return $sentCount;
    }

    private function markAsSent(ClassSchedule $classSchedule, StudentSchedule $studentSchedule): bool
    {
        try {
            ClassScheduleReminder::query()->create([
                'class_schedule_id' => $classSchedule->id,
                'student_schedule_id' => $studentSchedule->id,
                'sent_at' => now(),
            ]);

            return true;
        } catch (UniqueConstraintViolationException) {
            return false;
        }
    }
}
