<?php

declare(strict_types=1);

namespace NouTools\Domains\Home\Actions;

use App\Models\Course;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Date;
use NouTools\Domains\Home\DataTransferObjects\ShowHomePageData;
use NouTools\Domains\Home\PageData\HomePageData;
use NouTools\Domains\Home\ViewModels\HomeCourseViewModel;
use NouTools\Domains\Schedules\Actions\ReadStudentScheduleCookie;
use Spatie\LaravelData\DataCollection;

final readonly class ShowHomePage
{
    public function __construct(
        private ReadStudentScheduleCookie $readStudentScheduleCookie,
    ) {}

    public function __invoke(ShowHomePageData $input, Request $request): HomePageData
    {
        $selectedDate = $this->resolveSelectedDate($input->date);

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

        return new HomePageData(
            selectedDate: $selectedDate,
            courses: HomeCourseViewModel::collect(
                $courses->map(fn (Course $course) => HomeCourseViewModel::fromModel($course)),
                DataCollection::class,
            ),
            previousSchedule: ($this->readStudentScheduleCookie)($request),
        );
    }

    private function resolveSelectedDate(?string $date): string
    {
        try {
            return $date
                ? Date::createFromFormat('Y-m-d', $date, 'Asia/Taipei')->format('Y-m-d')
                : Date::now('Asia/Taipei')->format('Y-m-d');
        } catch (\Exception) {
            return Date::now('Asia/Taipei')->format('Y-m-d');
        }
    }
}
