<?php

namespace NouTools\Domains\Home\Actions;

use App\Models\Course;
use Carbon\Carbon;
use Illuminate\Http\Request;
use NouTools\Domains\Home\DataTransferObjects\ShowHomePageData;
use NouTools\Domains\Home\ViewModels\HomePageViewModel;
use NouTools\Domains\Schedules\Actions\ReadStudentScheduleCookie;

final readonly class ShowHomePage
{
    public function __construct(
        private ReadStudentScheduleCookie $readStudentScheduleCookie,
    ) {}

    public function __invoke(ShowHomePageData $input, Request $request): HomePageViewModel
    {
        $selectedDate = $this->resolveSelectedDate($input->date);

        $courses = Course::with(['classes' => function ($query) use ($selectedDate) {
            $query->with(['schedules' => function ($scheduleQuery) use ($selectedDate) {
                $scheduleQuery->whereDate('date', $selectedDate);
            }])->whereHas('schedules', function ($scheduleQuery) use ($selectedDate) {
                $scheduleQuery->whereDate('date', $selectedDate);
            });
        }])
            ->whereHas('classes.schedules', function ($query) use ($selectedDate) {
                $query->whereDate('date', $selectedDate);
            })
            ->get();

        return new HomePageViewModel(
            selectedDate: $selectedDate,
            courses: $courses,
            previousSchedule: ($this->readStudentScheduleCookie)($request),
        );
    }

    private function resolveSelectedDate(?string $date): string
    {
        try {
            return $date
                ? Carbon::createFromFormat('Y-m-d', $date, 'Asia/Taipei')->format('Y-m-d')
                : Carbon::now('Asia/Taipei')->format('Y-m-d');
        } catch (\Exception) {
            return Carbon::now('Asia/Taipei')->format('Y-m-d');
        }
    }
}
