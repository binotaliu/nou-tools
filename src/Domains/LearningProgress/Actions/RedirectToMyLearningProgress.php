<?php

declare(strict_types=1);

namespace NouTools\Domains\LearningProgress\Actions;

use App\Models\StudentSchedule;
use Illuminate\Contracts\Database\Query\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use NouTools\Domains\Schedules\Actions\ReadStudentScheduleCookie;

/**
 * Resolves the remembered schedule's learning progress page for the current
 * semester. Without a remembered schedule it sends the viewer to create one,
 * and when that schedule has no courses this semester (the progress page
 * would 404) it falls back to the schedule itself.
 */
final readonly class RedirectToMyLearningProgress
{
    public function __construct(private ReadStudentScheduleCookie $readStudentScheduleCookie) {}

    public function __invoke(Request $request): RedirectResponse
    {
        $cookie = ($this->readStudentScheduleCookie)($request);

        if ($cookie === null) {
            return redirect()->route('schedules.create');
        }

        $term = (string) config('app.current_semester');

        $hasCourses = StudentSchedule::query()
            ->whereKey($cookie->id)
            ->whereHas('items.courseClass.course', fn (Builder $query) => $query->where('term', $term))
            ->exists();

        if (! $hasCourses) {
            return redirect()->route('schedules.show', $cookie->token);
        }

        return redirect()->route('learning-progress.show', [
            'schedule' => $cookie->token,
            'term' => $term,
        ]);
    }
}
