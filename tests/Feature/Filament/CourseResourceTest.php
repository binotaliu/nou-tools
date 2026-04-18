<?php

use App\Filament\Resources\Courses\CourseResource;
use App\Filament\Resources\Courses\Resources\CourseClasses\CourseClassResource;
use App\Filament\Resources\Courses\Resources\CourseClasses\Resources\ClassSchedules\ClassScheduleResource;
use App\Filament\Resources\Courses\Resources\PreviousExams\PreviousExamResource;
use App\Filament\Resources\Courses\Resources\Textbooks\TextbookResource;
use App\Models\Course;
use App\Models\CourseClass;
use App\Models\PreviousExam;
use App\Models\Textbook;
use App\Models\User;

use function Pest\Laravel\actingAs;

test('authenticated users can access the course filament resource pages', function () {
    /** @var User $user */
    $user = User::factory()->createOne();
    $course = Course::factory()->create([
        'name' => '管理測試課程',
        'term' => '2025B',
    ]);

    actingAs($user)
        ->get(CourseResource::getUrl())
        ->assertSuccessful()
        ->assertSee('管理測試課程');

    actingAs($user)
        ->get(CourseResource::getUrl('create'))
        ->assertSuccessful()
        ->assertSee('課程名稱');

    actingAs($user)
        ->get(CourseResource::getUrl('edit', ['record' => $course]))
        ->assertSuccessful()
        ->assertSee('新增教科書');
});

test('nested filament resource pages resolve correctly for course data', function () {
    /** @var User $user */
    $user = User::factory()->createOne();
    $course = Course::factory()->create([
        'name' => '巢狀課程',
        'term' => '2025B',
    ]);
    $courseClass = CourseClass::factory()->create([
        'course_id' => $course->id,
        'code' => 'CLS101',
    ]);
    $textbook = Textbook::factory()->create([
        'course_id' => $course->id,
        'term' => $course->term,
        'book_title' => 'Filament 管理手冊',
    ]);
    $previousExam = PreviousExam::create([
        'course_name' => $course->name,
        'course_no' => 'NOU101',
        'term' => '114上學期',
        'midterm_reference_primary' => 'midterm.pdf',
        'midterm_reference_secondary' => null,
        'final_reference_primary' => 'final.pdf',
        'final_reference_secondary' => null,
    ]);
    $schedule = $courseClass->schedules()->create([
        'date' => '2026-05-01',
        'start_time' => '14:00',
        'end_time' => '15:50',
    ]);

    actingAs($user)
        ->get(CourseClassResource::getUrl('create', ['course' => $course]))
        ->assertSuccessful()
        ->assertSee('班級代碼');

    actingAs($user)
        ->get(CourseClassResource::getUrl('edit', ['course' => $course, 'record' => $courseClass]))
        ->assertSuccessful()
        ->assertSee('CLS101');

    actingAs($user)
        ->get(TextbookResource::getUrl('create', ['course' => $course]))
        ->assertSuccessful()
        ->assertSee('書名');

    actingAs($user)
        ->get(TextbookResource::getUrl('edit', ['course' => $course, 'record' => $textbook]))
        ->assertSuccessful()
        ->assertSee('Filament 管理手冊');

    actingAs($user)
        ->get(PreviousExamResource::getUrl('create', ['course' => $course]))
        ->assertSuccessful()
        ->assertSee('科目代號');

    actingAs($user)
        ->get(PreviousExamResource::getUrl('edit', ['course' => $course, 'record' => $previousExam]))
        ->assertSuccessful()
        ->assertSee('114上學期');

    actingAs($user)
        ->get(ClassScheduleResource::getUrl('create', [
            'course' => $course,
            'course_class' => $courseClass,
        ]))
        ->assertSuccessful()
        ->assertSee('覆寫開始時間');

    actingAs($user)
        ->get(ClassScheduleResource::getUrl('edit', [
            'course' => $course,
            'course_class' => $courseClass,
            'record' => $schedule,
        ]))
        ->assertSuccessful()
        ->assertSee('14:00');
});
