<?php

declare(strict_types=1);

namespace NouTools\Domains\Home\Actions;

use App\Models\Course;
use NouTools\Domains\Home\ViewModels\HomeCourseViewModel;
use Spatie\LaravelData\DataCollection;

/**
 * Courses with an official in-person (視訊面授) class on the given day, shared by the
 * homepage section and the standalone /video-classes page.
 */
final readonly class ListVideoCourses
{
    /**
     * @return DataCollection<int, HomeCourseViewModel>
     */
    public function __invoke(string $selectedDate): DataCollection
    {
        $courses = Course::with(['classes' => function ($query) use ($selectedDate) {
            $query->official()->with(['schedules' => function ($scheduleQuery) use ($selectedDate) {
                $scheduleQuery->whereDate('date', $selectedDate);
            }])->whereHas('schedules', function ($scheduleQuery) use ($selectedDate) {
                $scheduleQuery->whereDate('date', $selectedDate);
            });
        }])
            ->whereHas('classes', function ($query) use ($selectedDate) {
                $query->official()->whereHas('schedules', function ($scheduleQuery) use ($selectedDate) {
                    $scheduleQuery->whereDate('date', $selectedDate);
                });
            })
            ->get();

        return HomeCourseViewModel::collect(
            $courses->map(fn (Course $course) => HomeCourseViewModel::fromModel($course)),
            DataCollection::class,
        );
    }
}
