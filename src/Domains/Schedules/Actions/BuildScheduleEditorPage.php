<?php

declare(strict_types=1);

namespace NouTools\Domains\Schedules\Actions;

use App\Models\Course;
use App\Models\StudentSchedule;
use Illuminate\Http\Request;
use NouTools\Domains\Schedules\PageData\ScheduleEditorPageData;
use NouTools\Domains\Schedules\ViewModels\ScheduleEditorCourseViewModel;
use Spatie\LaravelData\DataCollection;

final readonly class BuildScheduleEditorPage
{
    public function __construct(
        private ReadStudentScheduleCookie $readStudentScheduleCookie,
    ) {}

    public function __invoke(Request $request, ?StudentSchedule $schedule = null): ScheduleEditorPageData
    {
        if ($schedule) {
            $schedule->load(['items.courseClass.course']);
        }

        $currentSemester = (string) config('app.current_semester');
        $selectedTerm = (string) ($request->query('term') ?: $currentSemester);
        $availableTerms = $this->availableTerms($selectedTerm);

        $courses = ScheduleEditorCourseViewModel::collect(
            Course::query()
                ->where('term', $selectedTerm)
                ->whereHas('classes')
                ->with(['classes' => function ($query) {
                    $query->orderBy('type');
                }])
                ->orderBy('name')
                ->get()
                ->map(fn (Course $course) => ScheduleEditorCourseViewModel::fromModel($course)),
            DataCollection::class,
        );

        $previousSchedule = null;

        if (! $schedule && ! $request->boolean('new')) {
            $previousSchedule = ($this->readStudentScheduleCookie)($request);
        }

        return new ScheduleEditorPageData(
            courses: $courses,
            currentSemester: $currentSemester,
            selectedTerm: $selectedTerm,
            availableTerms: $availableTerms,
            schedule: $schedule,
            previousSchedule: $previousSchedule,
        );
    }

    /**
     * @return array<int, string>
     */
    private function availableTerms(string $selectedTerm): array
    {
        $terms = Course::query()
            ->select('term')
            ->distinct()
            ->orderByDesc('term')
            ->pluck('term')
            ->filter(fn (?string $value) => is_string($value) && $value !== '')
            ->values();

        if (! $terms->contains($selectedTerm)) {
            $terms->prepend($selectedTerm);
        }

        return $terms->unique()->values()->all();
    }
}
