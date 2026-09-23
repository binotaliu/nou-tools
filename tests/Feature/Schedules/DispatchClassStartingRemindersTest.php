<?php

use App\Enums\ClassScheduleReminderStatus;
use App\Models\ClassSchedule;
use App\Models\ClassScheduleReminder;
use App\Models\CourseClass;
use App\Models\PushNotificationDelivery;
use App\Models\StudentSchedule;
use App\Models\StudentScheduleItem;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use NouTools\Domains\Schedules\Actions\DispatchClassStartingReminders;

// A real (but throwaway) P-256 keypair + auth secret: the webpush channel
// encrypts the payload for real in these tests (only the HTTP delivery is
// faked), so the subscription keys must be structurally valid.
const TEST_PUSH_PUBLIC_KEY = 'BPeI0YeBE3C3e-klFTupoIbmJmGvM1xPKn5rIFiNz8Uc3N5R8-keeX-WVmaNVAu0-5MTNzjx6NNNwIvCbnj1oW8';
const TEST_PUSH_AUTH_TOKEN = 'zJs6GqzLmdU4jVj56lFQIA';

function subscribedStudentSchedule(CourseClass $courseClass, string $endpoint = 'https://push.example.com/success/one'): StudentSchedule
{
    $schedule = StudentSchedule::create([
        'uuid' => Str::uuid(),
        'name' => '提醒測試',
    ]);

    $schedule->notify_on_class_start = true;
    $schedule->save();

    StudentScheduleItem::create([
        'student_schedule_id' => $schedule->id,
        'course_id' => $courseClass->course_id,
        'course_class_id' => $courseClass->id,
    ]);

    $schedule->updatePushSubscription(
        endpoint: $endpoint,
        key: TEST_PUSH_PUBLIC_KEY,
        token: TEST_PUSH_AUTH_TOKEN,
    );

    return $schedule;
}

beforeEach(function () {
    Carbon::setTestNow(Carbon::parse('2026-03-02 09:50:00', 'Asia/Taipei'));

    Http::fake([
        '*/success/*' => Http::response('', 201),
        '*/failure/*' => Http::response('', 500),
        '*/gone/*' => Http::response('', 410),
    ]);
});

afterEach(function () {
    Carbon::setTestNow();
});

it('sends a reminder for a class starting in ten minutes with a video link', function () {
    $courseClass = CourseClass::factory()->create(['link' => 'https://meet.example.com/abc']);
    $schedule = subscribedStudentSchedule($courseClass);

    ClassSchedule::factory()->create([
        'class_id' => $courseClass->id,
        'date' => today('Asia/Taipei'),
        'start_time' => '10:00',
    ]);

    $sentCount = app(DispatchClassStartingReminders::class)();

    expect($sentCount)->toBe(1);
    $this->assertDatabaseHas(ClassScheduleReminder::class, [
        'student_schedule_id' => $schedule->id,
        'status' => ClassScheduleReminderStatus::Sent->value,
    ]);
    $this->assertDatabaseHas(PushNotificationDelivery::class, [
        'subscribable_id' => (string) $schedule->id,
        'success' => true,
    ]);
});

it('does not send a duplicate reminder for the same occurrence', function () {
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
    $this->assertDatabaseCount('push_notification_deliveries', 1);
});

it('does not send a reminder for a class without a video link', function () {
    $courseClass = CourseClass::factory()->create(['link' => '']);
    subscribedStudentSchedule($courseClass);

    ClassSchedule::factory()->create([
        'class_id' => $courseClass->id,
        'date' => today('Asia/Taipei'),
        'start_time' => '10:00',
    ]);

    $sentCount = app(DispatchClassStartingReminders::class)();

    expect($sentCount)->toBe(0);
    $this->assertDatabaseCount('class_schedule_reminders', 0);
});

it('does not send a reminder to a schedule that has a subscription but has not opted in', function () {
    $courseClass = CourseClass::factory()->create(['link' => 'https://meet.example.com/abc']);
    $schedule = subscribedStudentSchedule($courseClass);
    $schedule->notify_on_class_start = false;
    $schedule->save();

    ClassSchedule::factory()->create([
        'class_id' => $courseClass->id,
        'date' => today('Asia/Taipei'),
        'start_time' => '10:00',
    ]);

    $sentCount = app(DispatchClassStartingReminders::class)();

    expect($sentCount)->toBe(0);
    $this->assertDatabaseCount('class_schedule_reminders', 0);
    $this->assertDatabaseCount('push_notification_deliveries', 0);
    Http::assertNothingSent();
});

it('does not send a reminder for a class outside the ten minute window', function () {
    $courseClass = CourseClass::factory()->create(['link' => 'https://meet.example.com/abc']);
    subscribedStudentSchedule($courseClass);

    ClassSchedule::factory()->create([
        'class_id' => $courseClass->id,
        'date' => today('Asia/Taipei'),
        'start_time' => '10:30',
    ]);

    $sentCount = app(DispatchClassStartingReminders::class)();

    expect($sentCount)->toBe(0);
    $this->assertDatabaseCount('class_schedule_reminders', 0);
});

it('records a failed delivery instead of marking the occurrence as sent', function () {
    $courseClass = CourseClass::factory()->create(['link' => 'https://meet.example.com/abc']);
    $schedule = subscribedStudentSchedule($courseClass, endpoint: 'https://push.example.com/failure/one');

    ClassSchedule::factory()->create([
        'class_id' => $courseClass->id,
        'date' => today('Asia/Taipei'),
        'start_time' => '10:00',
    ]);

    $sentCount = app(DispatchClassStartingReminders::class)();

    expect($sentCount)->toBe(0);
    $reminder = ClassScheduleReminder::query()->where('student_schedule_id', $schedule->id)->firstOrFail();
    expect($reminder->status)->toBe(ClassScheduleReminderStatus::Failed);
    expect($reminder->failure_reason)->not->toBeNull();
    expect($reminder->sent_at)->toBeNull();
});

it('retries a previously failed delivery on the next run', function () {
    $courseClass = CourseClass::factory()->create(['link' => 'https://meet.example.com/abc']);
    $schedule = subscribedStudentSchedule($courseClass, endpoint: 'https://push.example.com/failure/one');

    ClassSchedule::factory()->create([
        'class_id' => $courseClass->id,
        'date' => today('Asia/Taipei'),
        'start_time' => '10:00',
    ]);

    $action = app(DispatchClassStartingReminders::class);
    expect($action())->toBe(0);

    // The device (or the network) recovers before the window closes.
    $schedule->deletePushSubscription('https://push.example.com/failure/one');
    $schedule->updatePushSubscription(
        endpoint: 'https://push.example.com/success/one',
        key: TEST_PUSH_PUBLIC_KEY,
        token: TEST_PUSH_AUTH_TOKEN,
    );

    expect($action())->toBe(1);
    $this->assertDatabaseCount('class_schedule_reminders', 1);
    $this->assertDatabaseHas(ClassScheduleReminder::class, [
        'student_schedule_id' => $schedule->id,
        'status' => ClassScheduleReminderStatus::Sent->value,
    ]);
});

it('marks the occurrence as sent when at least one of several devices succeeds', function () {
    $courseClass = CourseClass::factory()->create(['link' => 'https://meet.example.com/abc']);
    $schedule = subscribedStudentSchedule($courseClass, endpoint: 'https://push.example.com/success/one');
    $schedule->updatePushSubscription(
        endpoint: 'https://push.example.com/failure/two',
        key: TEST_PUSH_PUBLIC_KEY,
        token: TEST_PUSH_AUTH_TOKEN,
    );

    ClassSchedule::factory()->create([
        'class_id' => $courseClass->id,
        'date' => today('Asia/Taipei'),
        'start_time' => '10:00',
    ]);

    $sentCount = app(DispatchClassStartingReminders::class)();

    expect($sentCount)->toBe(1);
    $this->assertDatabaseHas(ClassScheduleReminder::class, [
        'student_schedule_id' => $schedule->id,
        'status' => ClassScheduleReminderStatus::Sent->value,
    ]);
    $this->assertDatabaseCount('push_notification_deliveries', 2);
    $this->assertDatabaseHas(PushNotificationDelivery::class, ['endpoint' => 'https://push.example.com/success/one', 'success' => true]);
    $this->assertDatabaseHas(PushNotificationDelivery::class, ['endpoint' => 'https://push.example.com/failure/two', 'success' => false]);
});

it('prunes a subscription the push service reports as gone', function () {
    $courseClass = CourseClass::factory()->create(['link' => 'https://meet.example.com/abc']);
    $schedule = subscribedStudentSchedule($courseClass, endpoint: 'https://push.example.com/gone/one');

    ClassSchedule::factory()->create([
        'class_id' => $courseClass->id,
        'date' => today('Asia/Taipei'),
        'start_time' => '10:00',
    ]);

    $sentCount = app(DispatchClassStartingReminders::class)();

    expect($sentCount)->toBe(0);
    expect($schedule->pushSubscriptions()->count())->toBe(0);
    $this->assertDatabaseHas(PushNotificationDelivery::class, [
        'endpoint' => 'https://push.example.com/gone/one',
        'success' => false,
    ]);
});
