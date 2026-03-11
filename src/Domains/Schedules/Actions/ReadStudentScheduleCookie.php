<?php

namespace NouTools\Domains\Schedules\Actions;

use App\Models\StudentSchedule;
use Illuminate\Http\Request;
use NouTools\Domains\Schedules\ViewModels\StudentScheduleCookieViewModel;

final class ReadStudentScheduleCookie
{
    public function __invoke(Request $request): ?StudentScheduleCookieViewModel
    {
        $cookie = $request->cookie('student_schedule');

        if (! $cookie) {
            return null;
        }

        $data = json_decode($cookie, true);

        if (! is_array($data) || ! isset($data['id'], $data['uuid'])) {
            return null;
        }

        /** @var StudentSchedule|null $model */
        $model = StudentSchedule::query()->find($data['id']);

        if (! $model) {
            return null;
        }

        return StudentScheduleCookieViewModel::fromModel($model);
    }
}
