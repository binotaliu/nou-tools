<?php

use App\Enums\BaristaTrigger;
use App\Models\AcornTransaction;
use App\Models\CafeBaristaLine;
use App\Models\CafeOrder;
use App\Models\Course;
use App\Models\SchoolCalendarEvent;
use App\Models\StudentSchedule;
use App\Models\StudentScheduleItem;
use App\Models\StudyRoomProfile;
use Illuminate\Support\Facades\Date;

$baristaCookie = function (StudentSchedule $schedule): string {
    return json_encode(['id' => $schedule->id, 'uuid' => $schedule->uuid, 'name' => $schedule->name]);
};

// A returning student: has bought something before, so FirstVisit stays out of the way.
$regular = function () use (&$regular): StudentSchedule {
    $schedule = StudentSchedule::factory()->create();
    StudyRoomProfile::factory()->for($schedule, 'schedule')->create(['nickname' => '常客']);
    CafeOrder::factory()->expired()->for($schedule, 'schedule')->create();

    return $schedule;
};

beforeEach(function () {
    // Wednesday 22:00 in Taipei.
    Date::setTestNow(Date::parse('2026-11-04 22:00:00', 'Asia/Taipei'));
});

it('asks about the midterm when it is a week away', function () use ($baristaCookie, $regular) {
    $schedule = $regular();
    $course = Course::factory()->create([
        'name' => '虛構學概論',
        'term' => config('app.current_semester'),
        'midterm_date' => '2026-11-09',
    ]);
    StudentScheduleItem::query()->create(['student_schedule_id' => $schedule->id, 'course_id' => $course->id]);

    $this->withCredentials()->withCookie('student_schedule', $baristaCookie($schedule))
        ->getJson(route('cafe.barista'))
        ->assertOk()
        ->assertJsonPath('line.trigger', 'exam_upcoming')
        ->assertJsonPath('line.text', '快期中考了，準備得如何？考試範圍都看完了嗎？');
});

it('reacts to an exam today and one that just ended', function () use ($baristaCookie, $regular) {
    $schedule = $regular();
    $course = Course::factory()->create([
        'name' => '虛構學概論',
        'term' => config('app.current_semester'),
        'final_date' => '2026-11-04',
    ]);
    StudentScheduleItem::query()->create(['student_schedule_id' => $schedule->id, 'course_id' => $course->id]);
    $cookie = $baristaCookie($schedule);

    $this->withCredentials()->withCookie('student_schedule', $cookie)->getJson(route('cafe.barista'))
        ->assertJsonPath('line.trigger', 'exam_day')
        ->assertJsonPath('line.text', '今天期末考對吧？加油！');

    Date::setTestNow(Date::now()->addDays(2));

    $this->withCredentials()->withCookie('student_schedule', $cookie)->getJson(route('cafe.barista'))
        ->assertJsonPath('line.trigger', 'after_exam');
});

it('mentions the next school event with its countdown', function () use ($baristaCookie, $regular) {
    $schedule = $regular();
    SchoolCalendarEvent::factory()->create([
        'term' => config('app.current_semester'),
        'name' => '虛構選課日',
        'start_date' => '2026-11-10',
        'end_date' => '2026-11-12',
        'is_important' => true,
    ]);

    $this->withCredentials()->withCookie('student_schedule', $baristaCookie($schedule))
        ->getJson(route('cafe.barista'))
        ->assertJsonPath('line.trigger', 'school_event_upcoming')
        ->assertJsonPath('line.text', '虛構選課日還有 6 天，準備得如何？');
});

it('falls back to the clock for a visitor and never uses a line it cannot fill', function () {
    CafeBaristaLine::query()->delete();
    CafeBaristaLine::factory()->create(['trigger' => BaristaTrigger::Greeting, 'text' => '嗨 {nickname}，{course} 怎麼樣？']);
    CafeBaristaLine::factory()->create([
        'trigger' => BaristaTrigger::TimeOfDay,
        'conditions' => ['period' => 'late_night'],
        'text' => '不該出現：現在是晚上',
        'priority' => 10,
    ]);
    CafeBaristaLine::factory()->create([
        'trigger' => BaristaTrigger::TimeOfDay,
        'conditions' => ['period' => 'evening'],
        'text' => '晚上好',
        'priority' => 10,
    ]);

    $this->getJson(route('cafe.barista'))->assertJsonPath('line.text', '晚上好');

    CafeBaristaLine::query()->where('trigger', BaristaTrigger::TimeOfDay)->delete();

    // The only line left needs a nickname and a course the visitor lacks.
    $this->getJson(route('cafe.barista'))->assertJsonPath('line', null);
});

it('does not repeat the line it just said while another is available', function () {
    CafeBaristaLine::query()->delete();
    $first = CafeBaristaLine::factory()->create(['trigger' => BaristaTrigger::Greeting, 'text' => '甲', 'priority' => 5]);
    $second = CafeBaristaLine::factory()->create(['trigger' => BaristaTrigger::Greeting, 'text' => '乙', 'priority' => 5]);

    $seen = collect(range(1, 2))->map(fn () => $this->getJson(route('cafe.barista'))->json('line.text'));

    expect($seen->unique()->sort()->values()->all())->toBe(['乙', '甲']);

    // Once both were shown lately it still answers instead of going quiet.
    $this->getJson(route('cafe.barista'))->assertJsonStructure(['line' => ['id', 'text', 'mood', 'replies']]);
});

it('reads high-balance and low-balance lines from the ledger', function () use ($baristaCookie, $regular) {
    $schedule = $regular();
    AcornTransaction::factory()->for($schedule, 'schedule')->credit(20)->create();

    $this->withCredentials()->withCookie('student_schedule', $baristaCookie($schedule))
        ->getJson(route('cafe.barista'))
        ->assertJsonPath('line.trigger', 'acorns_many')
        ->assertJsonPath('line.text', '你已經存了 20 顆橡實！記得，橡實只能保存 30 天，記得在還沒壞掉前把他們花掉！');
});

it('answers an order with a thank-you or a sticky note offer that names the drink', function () use ($baristaCookie, $regular) {
    $schedule = $regular();

    $line = $this->withCredentials()->withCookie('student_schedule', $baristaCookie($schedule))
        ->getJson(route('cafe.barista', ['drink' => '拿鐵']))
        ->assertOk()
        ->json('line');

    expect($line['trigger'])->toBe('offer_sticky_note')
        ->and($line['text'])->toContain('便利貼')
        ->and($line['replies'])->toHaveCount(2)
        ->and($line['replies'][0]['action'])->toBe('note');
});

it('ships default lines for every trigger an editor can pick', function () {
    $seeded = CafeBaristaLine::query()->pluck('trigger')->map(fn (BaristaTrigger $trigger): string => $trigger->value)->unique();

    foreach (BaristaTrigger::cases() as $trigger) {
        expect($seeded)->toContain($trigger->value);
    }
});
