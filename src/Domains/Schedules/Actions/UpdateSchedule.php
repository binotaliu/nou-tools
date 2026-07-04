<?php

namespace NouTools\Domains\Schedules\Actions;

use App\Models\StudentSchedule;
use App\Models\StudentScheduleItem;
use Illuminate\Contracts\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use NouTools\Domains\Schedules\DataTransferObjects\StudentScheduleUpsertData;

final class UpdateSchedule
{
    public function __invoke(StudentSchedule $schedule, StudentScheduleUpsertData $input): StudentSchedule
    {
        return DB::transaction(function () use ($schedule, $input) {
            $currentSemester = (string) config('app.current_semester');

            $schedule->name = $input->name;
            $schedule->saveOrFail();

            $schedule->items()
                ->whereHas('courseClass.course', fn (Builder $query) => $query->where('term', $currentSemester))
                ->delete();

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
