<?php

declare(strict_types=1);

namespace NouTools\Domains\Schedules\Actions;

use App\Models\StudentSchedule;
use Illuminate\Http\Request;
use NouTools\Domains\Schedules\ValueObjects\StudentScheduleCookie;

final class ReadStudentScheduleCookie
{
    public function __invoke(Request $request): ?StudentScheduleCookie
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

        return StudentScheduleCookie::fromModel($model);
    }
}
