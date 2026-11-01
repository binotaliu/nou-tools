<?php

declare(strict_types=1);

namespace NouTools\Domains\Cafe\Actions;

use App\Models\AcornTransaction;
use App\Models\CafeOrder;
use App\Models\Course;
use App\Models\StudentScheduleItem;
use App\Models\StudyRoomProfile;
use App\Models\StudyRoomSession;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\Date;
use NouTools\Domains\Acorns\Actions\ReadAcornBalance;
use NouTools\Domains\Cafe\ValueObjects\BaristaContext;
use NouTools\Domains\Schedules\ValueObjects\StudentScheduleCookie;
use NouTools\Domains\Shared\Actions\ListUpcomingSchoolEvents;

/**
 * Collects what the barista can talk about. A visitor without a schedule gets
 * a context with only the clock in it, so the generic lines still work.
 */
final readonly class BuildBaristaContext
{
    /** How many days after an exam the barista still asks how it went. */
    private const RECENT_EXAM_DAYS = 3;

    public function __construct(
        private ListUpcomingSchoolEvents $listUpcomingSchoolEvents,
        private ReadAcornBalance $readAcornBalance,
    ) {}

    public function __invoke(?StudentScheduleCookie $viewer, ?string $drink = null): BaristaContext
    {
        $now = Date::now((string) config('app.schedule_timezone'));
        $today = $now->copy()->startOfDay();

        $nickname = null;
        $exam = null;
        $recentExam = null;
        $studyMinutes = 0;
        $acorns = 0;
        $isFirstVisit = false;

        if ($viewer !== null) {
            $nickname = StudyRoomProfile::query()
                ->where('student_schedule_id', $viewer->id)
                ->value('nickname');

            [$exam, $recentExam] = $this->examsFor($viewer->id, $today);

            $studyMinutes = intdiv((int) StudyRoomSession::query()
                ->excludingInPersonClass()
                ->where('student_schedule_id', $viewer->id)
                ->where('ended_at', '>=', $today->copy()->utc())
                ->sum('focus_seconds'), 60);

            $acorns = ($this->readAcornBalance)($viewer->id)->balance;
            $isFirstVisit = ! CafeOrder::query()->where('student_schedule_id', $viewer->id)->exists()
                && ! AcornTransaction::query()->where('student_schedule_id', $viewer->id)->where('amount', '<', 0)->exists();
        }

        return new BaristaContext(
            nickname: $nickname,
            period: $this->periodOf($now),
            isWeekend: $now->isWeekend(),
            exam: $exam,
            recentExam: $recentExam,
            event: $this->nextEvent($today),
            studyMinutesToday: $studyMinutes,
            acorns: $acorns,
            isFirstVisit: $isFirstVisit,
            canPostNote: $viewer !== null,
            drink: $drink,
        );
    }

    /**
     * @return 'morning'|'afternoon'|'evening'|'late_night'
     */
    private function periodOf(CarbonInterface $now): string
    {
        return match (true) {
            $now->hour >= 5 && $now->hour < 12 => 'morning',
            $now->hour >= 12 && $now->hour < 18 => 'afternoon',
            $now->hour >= 18 && $now->hour < 23 => 'evening',
            default => 'late_night',
        };
    }

    /**
     * @return array{0: array{kind: string, label: string, days: int, course: string}|null, 1: array{kind: string, label: string, daysSince: int, course: string}|null}
     */
    private function examsFor(int $scheduleId, CarbonInterface $today): array
    {
        $courses = Course::query()
            ->whereIn('id', StudentScheduleItem::query()
                ->where('student_schedule_id', $scheduleId)
                ->select('course_id'))
            ->where('term', (string) config('app.current_semester'))
            ->get(['id', 'name', 'midterm_date', 'final_date']);

        $upcoming = null;
        $recent = null;

        foreach ($courses as $course) {
            foreach (['midterm' => ['midterm_date', '期中考'], 'final' => ['final_date', '期末考']] as $kind => [$column, $label]) {
                $date = $course->{$column};

                if ($date === null) {
                    continue;
                }

                $days = (int) $today->diffInDays($date->copy()->startOfDay(), false);

                if ($days >= 0 && ($upcoming === null || $days < $upcoming['days'])) {
                    $upcoming = ['kind' => $kind, 'label' => $label, 'days' => $days, 'course' => $course->name];
                }

                if ($days < 0 && -$days <= self::RECENT_EXAM_DAYS && ($recent === null || -$days < $recent['daysSince'])) {
                    $recent = ['kind' => $kind, 'label' => $label, 'daysSince' => -$days, 'course' => $course->name];
                }
            }
        }

        return [$upcoming, $recent];
    }

    /**
     * @return array{name: string, days: int}|null
     */
    private function nextEvent(CarbonInterface $today): ?array
    {
        $events = ($this->listUpcomingSchoolEvents)($today->toDateString());

        if ($events === []) {
            return null;
        }

        $event = $events[0];
        $days = max(0, (int) $today->diffInDays(Date::parse($event['start'], (string) config('app.schedule_timezone'))->startOfDay(), false));

        return ['name' => $event['name'], 'days' => $days];
    }
}
