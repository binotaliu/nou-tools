<?php

namespace NouTools\Domains\Courses\Actions;

use App\Models\Course;
use NouTools\Domains\Courses\ViewModels\Api\CourseSummaryViewModel;
use Spatie\LaravelData\DataCollection;

/**
 * Returns a lightweight list of courses (ID + name) for the given term.
 * Defaults to the current semester when no term is specified.
 */
final readonly class ListCourses
{
    /**
     * @return DataCollection<int, CourseSummaryViewModel>
     */
    public function __invoke(?string $term = null): DataCollection
    {
        $term ??= config('app.current_semester');

        $courses = Course::query()
            ->where('term', $term)
            ->orderBy('name')
            ->get();

        return CourseSummaryViewModel::collect($courses, DataCollection::class);
    }
}
