<?php

use App\Models\ClassSchedule;
use App\Models\Course;
use App\Models\CourseClass;
use App\Models\PreviousExam;
use App\Models\Textbook;

use function Pest\Laravel\getJson;

// ── Course list ─────────────────────────────────────────────────────────────

it('returns empty data when no courses exist for the current semester', function (): void {
    config(['app.current_semester' => '2025B']);

    getJson('/api/v1/courses')
        ->assertOk()
        ->assertJsonCount(0);
});

it('returns courses for the current semester by default', function (): void {
    config(['app.current_semester' => '2025B']);
    Course::factory()->count(3)->create(['term' => '2025B']);
    Course::factory()->count(2)->create(['term' => '2024A']);

    getJson('/api/v1/courses')
        ->assertOk()
        ->assertJsonCount(3);
});

it('filters courses by the term query parameter', function (): void {
    Course::factory()->count(2)->create(['term' => '2024A']);
    Course::factory()->count(4)->create(['term' => '2025B']);

    getJson('/api/v1/courses?term=2024A')
        ->assertOk()
        ->assertJsonCount(2);
});

it('returns only id, name, and term in course list items', function (): void {
    Course::factory()->create(['term' => '2025B', 'name' => '微積分']);

    $item = getJson('/api/v1/courses?term=2025B')
        ->assertOk()
        ->json('0');

    expect($item)
        ->toHaveKey('id')
        ->toHaveKey('name', '微積分')
        ->toHaveKey('term', '2025B')
        ->not->toHaveKey('classes')
        ->not->toHaveKey('midtermDate');
});

// ── Course detail ────────────────────────────────────────────────────────────

it('returns 404 for a non-existent course', function (): void {
    getJson('/api/v1/courses/99999')->assertNotFound();
});

it('returns full course detail', function (): void {
    $course = Course::factory()->create([
        'term' => '2025B',
        'name' => '統計學',
        'midterm_date' => '2026-04-25',
        'final_date' => '2026-06-27',
        'exam_time_start' => '09:30',
        'exam_time_end' => '11:30',
    ]);

    $data = getJson("/api/v1/courses/{$course->id}")
        ->assertOk()
        ->json();

    expect($data)
        ->toHaveKey('id', $course->id)
        ->toHaveKey('name', '統計學')
        ->toHaveKey('term', '2025B')
        ->toHaveKey('midtermDate', '2026-04-25')
        ->toHaveKey('finalDate', '2026-06-27')
        ->toHaveKey('examTimeStart', '09:30')
        ->toHaveKey('examTimeEnd', '11:30')
        ->toHaveKey('textbook')
        ->toHaveKey('previousExams')
        ->toHaveKey('classes');
});

it('includes textbook in course detail when present', function (): void {
    $course = Course::factory()->create(['term' => '2025B']);
    Textbook::factory()->create([
        'course_id' => $course->id,
        'book_title' => '統計學導論',
        'edition' => '三版',
        'price_info' => '580元',
        'reference_url' => 'https://example.com/book',
    ]);

    $data = getJson("/api/v1/courses/{$course->id}")
        ->assertOk()
        ->json();

    expect($data['textbook'])
        ->toHaveKey('bookTitle', '統計學導論')
        ->toHaveKey('edition', '三版')
        ->toHaveKey('priceInfo', '580元')
        ->toHaveKey('referenceUrl', 'https://example.com/book');
});

it('returns null textbook when course has no textbook', function (): void {
    $course = Course::factory()->create(['term' => '2025B']);

    $data = getJson("/api/v1/courses/{$course->id}")
        ->assertOk()
        ->json();

    expect($data['textbook'])->toBeNull();
});

it('includes previous exams matched by course name', function (): void {
    $course = Course::factory()->create(['term' => '2025B', 'name' => '線性代數']);
    PreviousExam::create([
        'course_name' => '線性代數',
        'course_no' => 'MATH101',
        'term' => '2024B',
        'midterm_reference_primary' => 'https://example.com/mid1',
        'final_reference_primary' => 'https://example.com/fin1',
    ]);
    PreviousExam::create([
        'course_name' => '線性代數',
        'course_no' => 'MATH101',
        'term' => '2024A',
        'midterm_reference_primary' => 'https://example.com/mid2',
        'final_reference_primary' => null,
    ]);

    $data = getJson("/api/v1/courses/{$course->id}")
        ->assertOk()
        ->json();

    expect($data['previousExams'])->toHaveCount(2);
    expect($data['previousExams'][0])
        ->toHaveKey('term', '2024B')
        ->toHaveKey('midtermReferencePrimary', 'https://example.com/mid1');
});

it('includes classes with sessions in course detail', function (): void {
    $course = Course::factory()->create(['term' => '2025B']);
    $class = CourseClass::factory()->create([
        'course_id' => $course->id,
        'code' => 'ZZZ001',
        'start_time' => '09:00',
        'end_time' => '10:50',
        'teacher_name' => '王老師',
        'link' => 'https://vc.example.com/class',
    ]);
    ClassSchedule::factory()->create([
        'class_id' => $class->id,
        'date' => '2026-04-11',
        'start_time' => null,
        'end_time' => null,
    ]);
    ClassSchedule::factory()->withTimeOverride('14:00', '15:50')->create([
        'class_id' => $class->id,
        'date' => '2026-05-09',
    ]);

    $data = getJson("/api/v1/courses/{$course->id}")
        ->assertOk()
        ->json();

    expect($data['classes'])->toHaveCount(1);

    $cls = $data['classes'][0];
    expect($cls)
        ->toHaveKey('id', $class->id)
        ->toHaveKey('code', 'ZZZ001')
        ->toHaveKey('teacherName', '王老師')
        ->toHaveKey('link', 'https://vc.example.com/class');

    expect($cls['sessions'])->toHaveCount(2);
    expect($cls['sessions'][0])->toHaveKey('date', '2026-04-11')
        ->toHaveKey('startTime', '09:00');
    expect($cls['sessions'][1])->toHaveKey('date', '2026-05-09')
        ->toHaveKey('startTime', '14:00');
});
