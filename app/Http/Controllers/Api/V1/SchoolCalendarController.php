<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use NouTools\Domains\Shared\SchoolCalendar\Actions\GetCurrentSchoolCalendar;
use NouTools\Domains\Shared\SchoolCalendar\ViewModels\SchoolCalendarEventViewModel;
use Spatie\LaravelData\DataCollection;

final class SchoolCalendarController extends Controller
{
    /**
     * @return DataCollection<int, SchoolCalendarEventViewModel>
     */
    public function __invoke(GetCurrentSchoolCalendar $action): DataCollection
    {
        return $action();
    }
}
