<?php

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
    public function __invoke(Course $course): CourseDetailViewModel
    {
        $course->load([
            'textbook',
            'classes' => fn ($q) => $q->orderBy('type')->orderBy('code'),
            'classes.schedules' => fn ($q) => $q->orderBy('date'),
        ]);

        $previousExams = PreviousExam::query()
            ->where('course_name', $course->name)
            ->orderByDesc('term')
            ->get();

        return CourseDetailViewModel::fromModel($course, $previousExams);
    }
}
