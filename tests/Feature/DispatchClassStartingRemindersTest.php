<?php

use App\Models\ClassSchedule;
use App\Models\CourseClass;
use App\Models\StudentSchedule;
use App\Models\StudentScheduleItem;
use App\Notifications\ClassStartingSoon;
use Carbon\Carbon;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use NouTools\Domains\Schedules\Actions\DispatchClassStartingReminders;

function subscribedStudentSchedule(CourseClass $courseClass): StudentSchedule
{
    $schedule = StudentSchedule::create([
        'uuid' => Str::uuid(),
        'name' => '提醒測試',
    ]);

    StudentScheduleItem::create([
        'student_schedule_id' => $schedule->id,
        'course_id' => $courseClass->course_id,
        'course_class_id' => $courseClass->id,
    ]);

    $schedule->updatePushSubscription(
        endpoint: 'https://fcm.googleapis.com/fcm/send/'.Str::random(10),
        key: 'p256dh-key',
        token: 'auth-token',
    );

    return $schedule;
}

beforeEach(function () {
    Carbon::setTestNow(Carbon::parse('2026-03-02 09:50:00', 'Asia/Taipei'));
});

afterEach(function () {
    Carbon::setTestNow();
});

it('sends a reminder for a class starting in ten minutes with a video link', function () {
    Notification::fake();

    $courseClass = CourseClass::factory()->create(['link' => 'https://meet.example.com/abc']);
    $schedule = subscribedStudentSchedule($courseClass);

    ClassSchedule::factory()->create([
        'class_id' => $courseClass->id,
        'date' => today('Asia/Taipei'),
        'start_time' => '10:00',
    ]);

    $sentCount = app(DispatchClassStartingReminders::class)();

    expect($sentCount)->toBe(1);
    Notification::assertSentTo($schedule, ClassStartingSoon::class);
    $this->assertDatabaseCount('class_schedule_reminders', 1);
});

it('does not send a duplicate reminder for the same occurrence', function () {
    Notification::fake();

    $courseClass = CourseClass::factory()->create(['link' => 'https://meet.example.com/abc']);
    subscribedStudentSchedule($courseClass);

    ClassSchedule::factory()->create([
        'class_id' => $courseClass->id,
        'date' => today('Asia/Taipei'),
        'start_time' => '10:00',
    ]);

    $action = app(DispatchClassStartingReminders::class);
    $action();
    $sentCount = $action();

    expect($sentCount)->toBe(0);
    $this->assertDatabaseCount('class_schedule_reminders', 1);
});

it('does not send a reminder for a class without a video link', function () {
    Notification::fake();

    $courseClass = CourseClass::factory()->create(['link' => '']);
    subscribedStudentSchedule($courseClass);

    ClassSchedule::factory()->create([
        'class_id' => $courseClass->id,
        'date' => today('Asia/Taipei'),
        'start_time' => '10:00',
    ]);

    $sentCount = app(DispatchClassStartingReminders::class)();

    expect($sentCount)->toBe(0);
    Notification::assertNothingSent();
});

it('does not send a reminder for a class outside the ten minute window', function () {
    Notification::fake();

    $courseClass = CourseClass::factory()->create(['link' => 'https://meet.example.com/abc']);
    subscribedStudentSchedule($courseClass);

    ClassSchedule::factory()->create([
        'class_id' => $courseClass->id,
        'date' => today('Asia/Taipei'),
        'start_time' => '10:30',
    ]);

    $sentCount = app(DispatchClassStartingReminders::class)();

    expect($sentCount)->toBe(0);
    Notification::assertNothingSent();
});
