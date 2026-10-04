<?php

declare(strict_types=1);

namespace NouTools\Domains\Courses\Actions;

use App\Models\Course;
use App\Models\PreviousExam;
use NouTools\Domains\Courses\ViewModels\Api\CourseDetailViewModel;

/**
 * Loads full course details including exam dates, textbook, previous exams,
 * and all class sections with their in-person session dates and video links.
 */
final readonly class GetCourseDetail
{
    /**
     * @param  bool  $includePrograms  also load the 專班 classes; the public course page only shows 一般生 classes
     */
    public function __invoke(Course $course, bool $includePrograms = false): CourseDetailViewModel
    {
        $course->load([
            'textbook',
            'classes' => fn ($q) => $q->official()
                ->when(! $includePrograms, fn ($query) => $query->general())
                ->with('program')
                ->orderBy('type')
                ->orderBy('code'),
            'classes.schedules' => fn ($q) => $q->orderBy('date'),
        ]);

        $previousExams = PreviousExam::query()
            ->where('course_name', $course->name)
            ->orderByDesc('term')
            ->get();

        return CourseDetailViewModel::fromModel($course, $previousExams);
    }
}
