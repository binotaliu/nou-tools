<?php

namespace NouTools\Domains\Schedules\Actions;

use App\Models\StudentSchedule;
use App\Models\StudentScheduleItem;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use NouTools\Domains\Schedules\DataTransferObjects\StudentScheduleUpsertData;

final class CreateSchedule
{
    public function __invoke(StudentScheduleUpsertData $input): StudentSchedule
    {
        return DB::transaction(function () use ($input) {
            $schedule = new StudentSchedule;
            $schedule->uuid = Str::uuid()->toString();
            $schedule->name = $input->name;
            $schedule->saveOrFail();

            foreach ($input->items as $courseClassId) {
                $item = new StudentScheduleItem;
                $item->student_schedule_id = $schedule->id;
                $item->course_class_id = $courseClassId;
                $item->saveOrFail();
            }

            return $schedule;
        });
    }
}
