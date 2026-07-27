<?php

declare(strict_types=1);

namespace NouTools\Domains\Courses\Actions;

use App\Enums\CourseClassType;
use App\Models\Course;
use App\Models\CourseClass;
use Illuminate\Support\Collection;
use NouTools\Domains\Courses\PageData\CourseSchedulePageData;
use NouTools\Domains\Courses\ViewModels\CourseScheduleCourseViewModel;
use NouTools\Domains\Courses\ViewModels\CourseScheduleGroupViewModel;
use Spatie\LaravelData\DataCollection;

final readonly class BuildCourseSchedulePage
{
    public function __invoke(?string $term = null): CourseSchedulePageData
    {
        $currentSemester = (string) config('app.current_semester');
        $selectedTerm = $term ?: $currentSemester;

        $courses = Course::query()
            ->where('term', $selectedTerm)
            ->with('classes')
            ->orderBy('name')
            ->get();

        $microCreditOrRemote = $courses->filter(
            fn (Course $course) => $this->hasClassType($course, CourseClassType::FullRemote)
                || $this->hasClassType($course, CourseClassType::MicroCredit)
        );

        $general = $courses->diff($microCreditOrRemote)
            ->filter(fn (Course $course) => $course->final_date && $course->exam_time_start);

        $groups = $general
            ->groupBy(fn (Course $course) => sprintf(
                '%s|%s|%s',
                $course->final_date->dayOfWeekIso,
                $course->exam_time_start,
                $course->exam_time_end,
            ))
            ->map(fn (Collection $courses) => CourseScheduleGroupViewModel::fromCourses($courses))
            ->sortBy(fn (CourseScheduleGroupViewModel $group) => [$group->weekdayOrder, $group->examTimeStart])
            ->values();

        return new CourseSchedulePageData(
            currentSemester: $currentSemester,
            selectedTerm: $selectedTerm,
            availableTerms: $this->availableTerms($selectedTerm),
            groups: CourseScheduleGroupViewModel::collect($groups, DataCollection::class),
            microCreditOrRemoteCourses: CourseScheduleCourseViewModel::collect(
                $microCreditOrRemote->sortBy('name')->map(fn (Course $course) => CourseScheduleCourseViewModel::fromModel($course))->values(),
                DataCollection::class,
            ),
        );
    }

    private function hasClassType(Course $course, CourseClassType $type): bool
    {
        return $course->classes->contains(fn (CourseClass $class) => $class->type === $type);
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
