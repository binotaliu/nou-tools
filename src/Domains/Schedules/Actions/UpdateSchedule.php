<?php

namespace NouTools\Domains\Schedules\Actions;

use App\Models\StudentSchedule;
use App\Models\StudentScheduleItem;
use Illuminate\Support\Facades\DB;
use NouTools\Domains\Schedules\DataTransferObjects\StudentScheduleUpsertData;

final class UpdateSchedule
{
    public function __invoke(StudentSchedule $schedule, StudentScheduleUpsertData $input): StudentSchedule
    {
        return DB::transaction(function () use ($schedule, $input) {
            $schedule->name = $input->name;
            $schedule->saveOrFail();

            $schedule->items()->delete();

            foreach ($input->items as $courseClassId) {
                $item = new StudentScheduleItem;
                $item->student_schedule_id = $schedule->id;
                $item->course_class_id = $courseClassId;
                $item->saveOrFail();
            }

            $schedule->touch();

            return $schedule;
        });
    }
}
