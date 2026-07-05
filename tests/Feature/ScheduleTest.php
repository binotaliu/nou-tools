<?php

use App\Models\ClassSchedule;
use App\Models\Course;
use App\Models\CourseClass;
use App\Models\StudentSchedule;
use App\Models\StudentScheduleItem;
use Carbon\Carbon;
use Illuminate\Support\Str;

it('returns JSON and creates schedule on application/json POST', function () {
    $courseClass = CourseClass::factory()->create();

    $payload = [
        'name' => '測試課表',
        'items' => [$courseClass->id],
    ];

    $response = $this->postJson(route('schedules.store'), $payload);

    $response->assertStatus(200)
        ->assertJsonStructure(['success', 'redirect_url'])
        ->assertJson(['success' => true]);

    $this->assertDatabaseHas('student_schedules', [
        'name' => '測試課表',
    ]);
});

it('allows creating multiple schedules', function () {
    $courseClass = CourseClass::factory()->create();

    $payload = [
        'name' => '第一次',
        'items' => [$courseClass->id],
    ];

    $this->postJson(route('schedules.store'), $payload)->assertStatus(200);

    $payload['name'] = '第二次';
    $this->postJson(route('schedules.store'), $payload)->assertStatus(200);

    $this->assertDatabaseCount('student_schedules', 2);
    $this->assertDatabaseHas('student_schedules', ['name' => '第一次']);
    $this->assertDatabaseHas('student_schedules', ['name' => '第二次']);
});

it('rejects schedules with more than ten items', function () {
    $classes = CourseClass::factory()->count(11)->create();

    $payload = [
        'name' => 'Too Many',
        'items' => $classes->pluck('id')->all(),
    ];

    $this->postJson(route('schedules.store'), $payload)
        ->assertStatus(422)
        ->assertJsonValidationErrors('items');

    // also try update path
    $schedule = StudentSchedule::create([
        'uuid' => Str::uuid(),
        'name' => 'Existing',
    ]);

    $response = $this->putJson(route('schedules.update', $schedule), $payload);
    $response->assertStatus(422)->assertJsonValidationErrors('items');
});

it('returns an .ics calendar for a saved schedule and converts UTC+8 times to UTC', function () {
    $courseClass = CourseClass::factory()->create([
        'start_time' => '09:00',
        'end_time' => '10:00',
    ]);

    $schedule = StudentSchedule::create([
        'uuid' => Str::uuid(),
        'name' => 'ICS Export',
        'last_calendar_sync_at' => null,
    ]);

    StudentScheduleItem::create([
        'student_schedule_id' => $schedule->id,
        'course_class_id' => $courseClass->id,
    ]);

    $date = now()->addWeek()->toDateString();

    ClassSchedule::factory()->create([
        'class_id' => $courseClass->id,
        'date' => $date,
    ]);

    $response = $this->get(route('schedules.calendar', $schedule));

    $schedule->refresh();
    expect($schedule->last_calendar_sync_at)->not->toBeNull();

    $expectedStart = Carbon::createFromFormat('Y-m-d H:i', $date.' 09:00', 'Asia/Taipei')
        ->setTimezone('UTC')
        ->format('Ymd\THis\Z');

    $expectedEnd = Carbon::createFromFormat('Y-m-d H:i', $date.' 10:00', 'Asia/Taipei')
        ->setTimezone('UTC')
        ->format('Ymd\THis\Z');

    $response->assertStatus(200)
        ->assertHeader('Content-Type', 'text/calendar; charset=utf-8')
        ->assertSee('BEGIN:VCALENDAR')
        ->assertSee('X-WR-CALNAME:ICS Export')
        ->assertSee($courseClass->course->name)
        ->assertSee($courseClass->code)
        ->assertSee('DTSTART:'.$expectedStart)
        ->assertSee('DTEND:'.$expectedEnd);
});

it('saves calendar subscription settings as schedule defaults', function () {
    $schedule = StudentSchedule::create([
        'uuid' => Str::uuid(),
        'name' => 'Calendar Settings',
        'display_options' => [
            'show_greeting' => true,
            'show_schedule_items' => true,
            'show_common_links' => true,
            'show_class_dates' => true,
            'show_school_calendar' => true,
            'show_exam_info' => true,
            'show_share_section' => true,
            'show_print_button' => true,
        ],
    ]);

    $response = $this->putJson(route('schedules.calendar-settings.update', $schedule), [
        'include_school_calendar' => false,
        'include_exams' => true,
        'class_reminders_enabled' => true,
        'reminder_offsets' => [60, 15],
    ]);

    $response->assertSuccessful()
        ->assertJsonPath('success', true)
        ->assertJsonPath('calendar_settings.include_school_calendar', false)
        ->assertJsonPath('calendar_settings.include_exams', true)
        ->assertJsonPath('calendar_settings.class_reminders_enabled', true)
        ->assertJsonPath('calendar_settings.reminder_offsets', [60, 15]);

    $schedule->refresh();

    expect($schedule->display_options['calendar_settings'])->toBe([
        'include_school_calendar' => false,
        'include_exams' => true,
        'class_reminders_enabled' => true,
        'reminder_offsets' => [60, 15],
    ]);
});

it('saves calendar subscription settings with form post and flashes success', function () {
    $schedule = StudentSchedule::create([
        'uuid' => Str::uuid(),
        'name' => 'Calendar Form Settings',
        'display_options' => [
            'show_greeting' => true,
            'show_schedule_items' => true,
            'show_common_links' => true,
            'show_class_dates' => true,
            'show_school_calendar' => true,
            'show_exam_info' => true,
            'show_share_section' => true,
            'show_print_button' => true,
        ],
    ]);

    $response = $this->put(route('schedules.calendar-settings.update', $schedule), [
        'include_school_calendar' => '0',
        'include_exams' => '1',
        'class_reminders_enabled' => '1',
        'reminder_offsets' => ['60', ''],
    ]);

    $response->assertRedirect(route('schedules.subscribe', $schedule))
        ->assertSessionHas('success', '訂閱設定已儲存！');

    $schedule->refresh();

    expect($schedule->display_options['calendar_settings'])->toBe([
        'include_school_calendar' => false,
        'include_exams' => true,
        'class_reminders_enabled' => true,
        'reminder_offsets' => [60],
    ]);
});

it('calendar output includes school events exams and reminders when defaults are enabled', function () {
    config()->set('app.current_semester', '2026A');
    config()->set('school-schedules.2026A', [
        [
            'name' => '學校重要日',
            'start' => '2026-03-01',
            'end' => '2026-03-01',
            'countdown' => true,
        ],
    ]);

    $course = Course::factory()->create([
        'name' => '提醒測試課程',
        'midterm_date' => '2026-04-25',
        'final_date' => null,
        'exam_time_start' => '13:30',
        'exam_time_end' => '14:30',
    ]);

    $courseClass = CourseClass::factory()->create([
        'course_id' => $course->id,
        'code' => 'REM101',
        'start_time' => '09:00',
        'end_time' => '10:00',
    ]);

    $schedule = StudentSchedule::create([
        'uuid' => Str::uuid(),
        'name' => 'Calendar Full',
        'display_options' => [
            'show_greeting' => true,
            'show_schedule_items' => true,
            'show_common_links' => true,
            'show_class_dates' => true,
            'show_school_calendar' => true,
            'show_exam_info' => true,
            'show_share_section' => true,
            'show_print_button' => true,
            'calendar_settings' => [
                'include_school_calendar' => true,
                'include_exams' => true,
                'class_reminders_enabled' => true,
                'reminder_offsets' => [30, 5],
            ],
        ],
    ]);

    StudentScheduleItem::create([
        'student_schedule_id' => $schedule->id,
        'course_class_id' => $courseClass->id,
    ]);

    ClassSchedule::factory()->create([
        'class_id' => $courseClass->id,
        'date' => '2026-03-12',
    ]);

    $response = $this->get(route('schedules.calendar', $schedule));

    $response->assertSuccessful()
        ->assertSee('SUMMARY:學校重要日')
        ->assertSee('SUMMARY:提醒測試課程 - 期中考')
        ->assertSee('BEGIN:VALARM')
        ->assertSee('TRIGGER:-PT30M')
        ->assertSee('TRIGGER:-PT5M');
});

it('calendar output excludes school events exams and reminders when defaults are disabled', function () {
    config()->set('app.current_semester', '2026A');
    config()->set('school-schedules.2026A', [
        [
            'name' => '不應出現的學校事件',
            'start' => '2026-03-01',
            'end' => '2026-03-01',
            'countdown' => true,
        ],
    ]);

    $course = Course::factory()->create([
        'name' => '不應出現的考試課程',
        'midterm_date' => '2026-04-25',
        'final_date' => null,
        'exam_time_start' => '13:30',
        'exam_time_end' => '14:30',
    ]);

    $courseClass = CourseClass::factory()->create([
        'course_id' => $course->id,
        'code' => 'DIS101',
        'start_time' => '09:00',
        'end_time' => '10:00',
    ]);

    $schedule = StudentSchedule::create([
        'uuid' => Str::uuid(),
        'name' => 'Calendar Limited',
        'display_options' => [
            'show_greeting' => true,
            'show_schedule_items' => true,
            'show_common_links' => true,
            'show_class_dates' => true,
            'show_school_calendar' => true,
            'show_exam_info' => true,
            'show_share_section' => true,
            'show_print_button' => true,
            'calendar_settings' => [
                'include_school_calendar' => false,
                'include_exams' => false,
                'class_reminders_enabled' => false,
                'reminder_offsets' => [30],
            ],
        ],
    ]);

    StudentScheduleItem::create([
        'student_schedule_id' => $schedule->id,
        'course_class_id' => $courseClass->id,
    ]);

    ClassSchedule::factory()->create([
        'class_id' => $courseClass->id,
        'date' => '2026-03-12',
    ]);

    $response = $this->get(route('schedules.calendar', $schedule));

    $response->assertSuccessful()
        ->assertDontSee('SUMMARY:不應出現的學校事件')
        ->assertDontSee('SUMMARY:不應出現的考試課程 - 期中考')
        ->assertDontSee('BEGIN:VALARM');
});

it('schedule show page displays exam information for selected courses', function () {
    config()->set('app.current_semester', '2025B');

    $course = Course::factory()->create([
        'name' => 'Exam Course From Schedule',
        'midterm_date' => '2025-04-25',
        'final_date' => '2025-06-27',
        'exam_time_start' => '13:30',
        'exam_time_end' => '14:40',
    ]);

    $class = CourseClass::factory()->create([
        'course_id' => $course->id,
        'code' => 'EXM101',
    ]);

    $schedule = StudentSchedule::create([
        'uuid' => Str::uuid(),
        'name' => 'Exam Schedule',
    ]);

    StudentScheduleItem::create([
        'student_schedule_id' => $schedule->id,
        'course_class_id' => $class->id,
    ]);

    $response = $this->get(route('schedules.show', $schedule));

    $response->assertStatus(200)
        ->assertSee('考試資訊')
        ->assertSee('期中考')
        ->assertSee('期末考')
        ->assertSee('4/25')
        ->assertSee('6/27')
        ->assertSee('EXM101');

    $this->assertMatchesRegularExpression('/13:30\s*-\s*14:40/', $response->getContent());
});

it('schedule show page defaults to current semester courses and updates learning progress link', function () {
    config()->set('app.current_semester', '2026C');

    $currentCourse = Course::factory()->create([
        'name' => 'Current Semester Course',
        'term' => '2026C',
    ]);
    $currentClass = CourseClass::factory()->create([
        'course_id' => $currentCourse->id,
        'code' => 'CUR101',
    ]);

    $otherCourse = Course::factory()->create([
        'name' => 'Other Semester Course',
        'term' => '2025B',
    ]);
    $otherClass = CourseClass::factory()->create([
        'course_id' => $otherCourse->id,
        'code' => 'OLD101',
    ]);

    $schedule = StudentSchedule::create([
        'uuid' => Str::uuid(),
        'name' => 'Term Filtered Schedule',
    ]);

    StudentScheduleItem::create([
        'student_schedule_id' => $schedule->id,
        'course_class_id' => $currentClass->id,
    ]);
    StudentScheduleItem::create([
        'student_schedule_id' => $schedule->id,
        'course_class_id' => $otherClass->id,
    ]);

    $response = $this->get(route('schedules.show', $schedule));

    $response->assertStatus(200)
        ->assertSee('Current Semester Course')
        ->assertSee('CUR101')
        ->assertDontSee('Other Semester Course')
        ->assertDontSee('OLD101')
        ->assertSee(route('learning-progress.show', [$schedule, '2026C']), false)
        ->assertSee('name="term"', false)
        ->assertSee('value="2026C"', false);
});

it('schedule show page shows empty state for selected semester without courses', function () {
    config()->set('app.current_semester', '2026C');

    $otherCourse = Course::factory()->create([
        'name' => 'Only Other Semester Course',
        'term' => '2025B',
    ]);
    $otherClass = CourseClass::factory()->create([
        'course_id' => $otherCourse->id,
    ]);

    $schedule = StudentSchedule::create([
        'uuid' => Str::uuid(),
        'name' => 'No Current Semester Course',
    ]);

    StudentScheduleItem::create([
        'student_schedule_id' => $schedule->id,
        'course_class_id' => $otherClass->id,
    ]);

    $response = $this->get(route('schedules.show', ['schedule' => $schedule, 'term' => '2026C']));

    $response->assertStatus(200)
        ->assertSee('此學期尚無課程')
        ->assertSee('沒有課程。');
});

it('stores schedule metadata in an encrypted cookie when saving', function () {
    $courseClass = CourseClass::factory()->create();

    $payload = [
        'name' => 'Cookie Test',
        'items' => [$courseClass->id],
    ];

    $response = $this->postJson(route('schedules.store'), $payload);

    $response->assertStatus(200)
        ->assertJson(['success' => true]);

    $schedule = StudentSchedule::where('name', 'Cookie Test')->first();
    expect($schedule)->not->toBeNull();

    $response->assertCookie('student_schedule', json_encode([
        'id' => $schedule->id,
        'uuid' => $schedule->uuid,
        'name' => 'Cookie Test',
    ]));
});

it('shows previous schedule on home when cookie exists', function () {
    $schedule = StudentSchedule::create([
        'uuid' => Str::uuid(),
        'name' => 'Previously Saved',
    ]);

    $response = $this->withCookie('student_schedule', json_encode([
        'id' => $schedule->id,
        'uuid' => $schedule->uuid,
        'name' => $schedule->name,
    ]))->get(route('home'));

    $response->assertStatus(200)
        ->assertSee('Previously Saved')
        ->assertSee(route('schedules.show', $schedule));
});

it('shows prompt on schedule create page when cookie exists and can be ignored with ?new=1', function () {
    $schedule = StudentSchedule::create([
        'uuid' => Str::uuid(),
        'name' => 'My Old Schedule',
    ]);

    $response = $this->withCookie('student_schedule', json_encode([
        'id' => $schedule->id,
        'uuid' => $schedule->uuid,
        'name' => $schedule->name,
    ]))->get(route('schedules.create'));

    $response->assertStatus(200)
        ->assertSee('你曾建立過課表')
        ->assertSee('My Old Schedule')
        ->assertSee(route('schedules.show', $schedule));

    $response2 = $this->withCookie('student_schedule', json_encode([
        'id' => $schedule->id,
        'uuid' => $schedule->uuid,
        'name' => $schedule->name,
    ]))->get(route('schedules.create').'?new=1');

    $response2->assertStatus(200)
        ->assertDontSee('你曾建立過課表');
});

it('updates the stored cookie when schedule is updated', function () {
    $courseClass = CourseClass::factory()->create();

    $schedule = StudentSchedule::create([
        'uuid' => Str::uuid(),
        'name' => 'Old Name',
    ]);

    $payload = [
        'name' => 'New Name',
        'items' => [$courseClass->id],
    ];

    $response = $this->put(route('schedules.update', $schedule), $payload);

    $response->assertRedirect(route('schedules.show', $schedule));
    $response->assertCookie('student_schedule', json_encode([
        'id' => $schedule->id,
        'uuid' => $schedule->uuid,
        'name' => 'New Name',
    ]));
});

it('updating schedule only replaces classes in current semester', function () {
    config()->set('app.current_semester', '2026C');

    $currentCourseA = Course::factory()->create(['term' => '2026C']);
    $currentCourseB = Course::factory()->create(['term' => '2026C']);
    $otherTermCourse = Course::factory()->create(['term' => '2025B']);

    $currentClassA = CourseClass::factory()->create(['course_id' => $currentCourseA->id]);
    $currentClassB = CourseClass::factory()->create(['course_id' => $currentCourseB->id]);
    $otherTermClass = CourseClass::factory()->create(['course_id' => $otherTermCourse->id]);

    $schedule = StudentSchedule::create([
        'uuid' => Str::uuid(),
        'name' => 'Cross Term Schedule',
    ]);

    StudentScheduleItem::create([
        'student_schedule_id' => $schedule->id,
        'course_class_id' => $currentClassA->id,
    ]);
    StudentScheduleItem::create([
        'student_schedule_id' => $schedule->id,
        'course_class_id' => $otherTermClass->id,
    ]);

    $response = $this->putJson(route('schedules.update', $schedule), [
        'name' => 'Cross Term Schedule Updated',
        'items' => [$currentClassB->id],
    ]);

    $response->assertSuccessful();

    $this->assertDatabaseMissing('student_schedule_items', [
        'student_schedule_id' => $schedule->id,
        'course_class_id' => $currentClassA->id,
    ]);

    $this->assertDatabaseHas('student_schedule_items', [
        'student_schedule_id' => $schedule->id,
        'course_class_id' => $currentClassB->id,
    ]);

    $this->assertDatabaseHas('student_schedule_items', [
        'student_schedule_id' => $schedule->id,
        'course_class_id' => $otherTermClass->id,
    ]);
});

it('edit page form posts to update route and includes method spoofing', function () {
    $schedule = StudentSchedule::create([
        'uuid' => Str::uuid(),
        'name' => 'Edit Me',
    ]);

    $response = $this->get(route('schedules.edit', $schedule));

    $response->assertStatus(200)
        ->assertSee('action="'.route('schedules.update', $schedule).'"', false)
        ->assertSee('name="_method" value="PUT"', false)
        // heading and button should reflect editing state
        ->assertSee('編輯您的課表')
        ->assertSee('更新課表');
});

it('create page form posts to store route and does not include method spoofing', function () {
    $response = $this->get(route('schedules.create'));

    $response->assertStatus(200)
        ->assertSee('action="'.route('schedules.store').'"', false)
        ->assertDontSee('name="_method" value="PUT"', false)
        // heading and button should reflect creation state
        ->assertSee('建立您的課表')
        ->assertSee('建立課表')
        // the form uses Alpine to render hidden inputs for selected course classes
        ->assertSee('template x-for="item in selectedItems"', false)
        ->assertSee('name="name"', false); // schedule name field should have a name attribute
});

it('customize page can update display options and custom links', function () {
    $schedule = StudentSchedule::create([
        'uuid' => Str::uuid(),
        'name' => 'Customize Me',
    ]);

    $response = $this->put(route('schedules.customize.update', $schedule), [
        'display_options' => [
            'show_greeting' => 0,
            'show_schedule_items' => 1,
            'show_common_links' => 1,
            'show_class_dates' => 1,
            'show_school_calendar' => 0,
            'show_exam_info' => 1,
            'show_share_section' => 1,
            'show_print_button' => 0,
        ],
        'custom_links' => [
            ['title' => '  學習群組  ', 'url' => '  https://help.nou.edu.tw/group  '],
            ['title' => '', 'url' => ''],
        ],
    ]);

    $response->assertRedirect(route('schedules.show', $schedule));

    $schedule->refresh();

    expect($schedule->display_options)->toBe([
        'show_greeting' => false,
        'show_schedule_items' => true,
        'show_common_links' => true,
        'show_class_dates' => true,
        'show_school_calendar' => false,
        'show_exam_info' => true,
        'show_share_section' => true,
        'show_print_button' => false,
    ]);

    expect($schedule->custom_links)->toBe([
        [
            'title' => '學習群組',
            'url' => 'https://help.nou.edu.tw/group',
        ],
    ]);
});

it('customize page rejects custom link domains outside allowed list', function () {
    $schedule = StudentSchedule::create([
        'uuid' => Str::uuid(),
        'name' => 'Bad Domain',
    ]);

    $response = $this->from(route('schedules.customize', $schedule))
        ->put(route('schedules.customize.update', $schedule), [
            'display_options' => [
                'show_greeting' => 1,
                'show_schedule_items' => 1,
                'show_common_links' => 1,
                'show_class_dates' => 1,
                'show_school_calendar' => 1,
                'show_exam_info' => 1,
                'show_share_section' => 1,
                'show_print_button' => 1,
            ],
            'custom_links' => [
                ['title' => '測試', 'url' => 'https://example.com/bad'],
            ],
        ]);

    $response->assertRedirect(route('schedules.customize', $schedule));
    $response->assertSessionHasErrors(['custom_links.0.url']);
});

it('customize page preserves old input after validation failure', function () {
    $schedule = StudentSchedule::create([
        'uuid' => Str::uuid(),
        'name' => 'Preserve Input',
    ]);

    $response = $this->followingRedirects()
        ->from(route('schedules.customize', $schedule))
        ->put(route('schedules.customize.update', $schedule), [
            'display_options' => [
                'show_greeting' => 1,
                'show_schedule_items' => 1,
                'show_common_links' => 1,
                'show_class_dates' => 1,
                'show_school_calendar' => 1,
                'show_exam_info' => 1,
                'show_share_section' => 1,
                'show_print_button' => 1,
            ],
            'custom_links' => [
                ['title' => '我的課程群組', 'url' => 'https://example.com/bad'],
            ],
        ]);

    $response->assertStatus(200)
        ->assertSee('links: JSON.parse')
        ->assertSee('example.com');
});

it('schedule show page hides disabled sections and shows custom links', function () {
    $schedule = StudentSchedule::create([
        'uuid' => Str::uuid(),
        'name' => 'Customized Schedule',
        'display_options' => [
            'show_greeting' => false,
            'show_alt_uu_banner' => false,
            'show_schedule_items' => true,
            'show_common_links' => true,
            'show_class_dates' => true,
            'show_school_calendar' => false,
            'show_exam_info' => true,
            'show_share_section' => false,
            'show_print_button' => false,
        ],
        'custom_links' => [
            ['title' => '我的自訂連結', 'url' => 'https://example.com/help'],
        ],
    ]);

    $response = $this->get(route('schedules.show', $schedule));

    $response->assertStatus(200)
        ->assertDontSee('今天是')
        ->assertDontSee('複製連結')
        ->assertDontSee('列印')
        ->assertSee('我的自訂連結')
        ->assertSee('https://example.com/help');
});

it('can view the schedule subscribe page', function () {
    $schedule = StudentSchedule::create([
        'uuid' => Str::uuid(),
        'name' => 'Test Schedule',
        'display_options' => [],
        'custom_links' => [],
    ]);

    $response = $this->get(route('schedules.subscribe', $schedule));

    $response->assertStatus(200)
        ->assertViewIs('schedule.subscribe')
        ->assertViewHas('viewModel');
});

it('subscribe page contains calendar subscription options', function () {
    $schedule = StudentSchedule::create([
        'uuid' => Str::uuid(),
        'name' => 'Test Schedule',
        'display_options' => [],
        'custom_links' => [],
    ]);

    $response = $this->get(route('schedules.subscribe', $schedule));

    $response->assertStatus(200)
        ->assertSee('Apple 日曆')
        ->assertSee('Google 日曆')
        ->assertSee('Windows 日曆')
        ->assertSee('訂閱行事曆')
        ->assertSee('訂閱設定');
});

it('schedule show page includes link to subscribe page', function () {
    $courseClass = CourseClass::factory()->create();
    $schedule = StudentSchedule::create([
        'uuid' => Str::uuid(),
        'name' => 'Test Schedule',
        'display_options' => [],
        'custom_links' => [],
    ]);
    StudentScheduleItem::create([
        'student_schedule_id' => $schedule->id,
        'course_class_id' => $courseClass->id,
    ]);

    $response = $this->get(route('schedules.show', $schedule));

    $response->assertStatus(200)
        ->assertSee(route('schedules.subscribe', $schedule))
        ->assertSee('訂閱行事曆');
});
