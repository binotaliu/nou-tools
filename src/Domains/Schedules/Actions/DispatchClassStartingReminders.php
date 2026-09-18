<?php

declare(strict_types=1);

namespace NouTools\Domains\Schedules\Actions;

use App\Enums\ClassScheduleReminderStatus;
use App\Models\ClassSchedule;
use App\Models\ClassScheduleReminder;
use App\Models\PushNotificationDelivery;
use App\Models\StudentSchedule;
use App\Notifications\ClassStartingSoon;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Str;
use Throwable;

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
            $startsAt = Date::parse(
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
                $reminder = $this->reserveReminder($classSchedule, $studentSchedule);

                if ($reminder === null) {
                    continue;
                }

                $outcome = $this->attemptDelivery($classSchedule, $studentSchedule);

                $reminder->update([
                    'status' => $outcome['status'],
                    'failure_reason' => $outcome['reason'],
                    'sent_at' => $outcome['status'] === ClassScheduleReminderStatus::Sent ? now() : null,
                ]);

                if ($outcome['status'] === ClassScheduleReminderStatus::Sent) {
                    $sentCount++;
                }
            }
        }

        return $sentCount;
    }

    /**
     * Reserve the (class, student) occurrence so concurrent runs don't send
     * it twice, without permanently blocking retries: a prior "failed" row
     * is handed back for another attempt, and only "sent" is treated as
     * done.
     */
    private function reserveReminder(ClassSchedule $classSchedule, StudentSchedule $studentSchedule): ?ClassScheduleReminder
    {
        $reminder = ClassScheduleReminder::query()
            ->where('class_schedule_id', $classSchedule->id)
            ->where('student_schedule_id', $studentSchedule->id)
            ->first();

        if ($reminder?->status === ClassScheduleReminderStatus::Sent) {
            return null;
        }

        if ($reminder !== null) {
            return $reminder;
        }

        try {
            return ClassScheduleReminder::query()->create([
                'class_schedule_id' => $classSchedule->id,
                'student_schedule_id' => $studentSchedule->id,
                'status' => ClassScheduleReminderStatus::Failed,
            ]);
        } catch (UniqueConstraintViolationException) {
            // A concurrent run just claimed this occurrence; let it handle the send.
            return null;
        }
    }

    /**
     * @return array{status: ClassScheduleReminderStatus, reason: ?string}
     */
    private function attemptDelivery(ClassSchedule $classSchedule, StudentSchedule $studentSchedule): array
    {
        $lastDeliveryId = (int) PushNotificationDelivery::query()->max('id');

        try {
            $studentSchedule->notify(new ClassStartingSoon($classSchedule));
        } catch (Throwable $exception) {
            report($exception);

            return ['status' => ClassScheduleReminderStatus::Failed, 'reason' => Str::limit($exception->getMessage(), 255, '')];
        }

        // A student schedule may have multiple push subscriptions (multiple
        // devices); each device's outcome is logged independently by
        // LogWebPushNotificationSent/Failed. Treat the occurrence as sent once any
        // device received it.
        $deliveries = PushNotificationDelivery::query()
            ->where('id', '>', $lastDeliveryId)
            ->where('subscribable_type', $studentSchedule->getMorphClass())
            ->where('subscribable_id', $studentSchedule->getKey())
            ->get();

        if ($deliveries->contains('success', true)) {
            return ['status' => ClassScheduleReminderStatus::Sent, 'reason' => null];
        }

        $reason = $deliveries->isEmpty()
            ? 'No push subscriptions were attempted'
            : $deliveries->pluck('reason')->filter()->unique()->implode('; ');

        return ['status' => ClassScheduleReminderStatus::Failed, 'reason' => Str::limit($reason, 255, '')];
    }
}
