<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Course;
use Illuminate\Http\Request;
use NouTools\Domains\Courses\Actions\GetCourseDetail;
use NouTools\Domains\Courses\Actions\ListCourses;
use NouTools\Domains\Courses\ViewModels\Api\CourseDetailViewModel;
use NouTools\Domains\Courses\ViewModels\Api\CourseSummaryViewModel;
use Spatie\LaravelData\DataCollection;

final class CourseController extends Controller
{
    /**
     * @return DataCollection<int, CourseSummaryViewModel>
     */
    public function index(Request $request, ListCourses $action): DataCollection
    {
        $term = $request->query('term');

        return $action($term);
    }

    public function show(Course $course, GetCourseDetail $action): CourseDetailViewModel
    {
        return $action($course);
    }
}
