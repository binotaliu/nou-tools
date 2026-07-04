<?php

namespace NouTools\Domains\Schedules\Actions;

use App\Models\StudentSchedule;
use Illuminate\Contracts\Database\Query\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use NouTools\Domains\Schedules\ViewModels\ScheduleViewModel;

final class ShowSchedulePage
{
    public function __invoke(StudentSchedule $schedule, ?string $term = null): ScheduleViewModel
    {
        $selectedTerm = $term ?: (string) config('app.current_semester');

        $availableTerms = $this->availableTerms($schedule, $selectedTerm);

        $schedule->load([
            'items' => fn (HasMany $query) => $query->whereHas(
                'courseClass.course',
                fn (Builder $courseQuery) => $courseQuery->where('term', $selectedTerm),
            ),
            'items.courseClass.course' => fn (BelongsTo $query) => $query->where('term', $selectedTerm),
            'items.courseClass.schedules',
        ]);

        return ScheduleViewModel::fromModel(
            schedule: $schedule,
            selectedTerm: $selectedTerm,
            availableTerms: $availableTerms,
        );
    }

    /**
     * @return array<int, string>
     */
    private function availableTerms(StudentSchedule $schedule, string $selectedTerm): array
    {
        $terms = $schedule->items()
            ->join('course_classes', 'student_schedule_items.course_class_id', '=', 'course_classes.id')
            ->join('courses', 'course_classes.course_id', '=', 'courses.id')
            ->select('courses.term')
            ->distinct()
            ->orderByDesc('courses.term')
            ->pluck('courses.term')
            ->filter(fn (?string $value) => is_string($value) && $value !== '')
            ->values();

        if (! $terms->contains($selectedTerm)) {
            $terms->prepend($selectedTerm);
        }

        return $terms
            ->unique()
            ->values()
            ->all();
    }
}
