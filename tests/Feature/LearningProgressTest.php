<?php

use App\Models\Course;
use App\Models\CourseClass;
use App\Models\LearningProgress;
use App\Models\StudentSchedule;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('can view learning progress page', function () {
    $schedule = StudentSchedule::factory()->create();

    // add a course for the term so the page is accessible
    $courseClass = CourseClass::factory()
        ->for(
            Course::factory()->state(['term' => '2025B'])
        )
        ->create();

    $schedule->items()->create(['course_id' => $courseClass->course_id, 'course_class_id' => $courseClass->id]);

    $response = $this->get(route('learning-progress.show', [
        'schedule' => $schedule,
        'term' => '2025B',
    ]));

    $response->assertStatus(200);
    $response->assertViewHas('viewModel');
    $response->assertViewIs('learning-progress.show');

    // progress bar text should be present, default 0%
    $response->assertSee('完成進度');
});

test('returns 404 when schedule has no courses for the term', function () {
    $schedule = StudentSchedule::factory()->create();

    // no items created for any term, so 2025B should be empty
    $response = $this->get(route('learning-progress.show', [
        'schedule' => $schedule,
        'term' => '2025B',
    ]));

    $response->assertStatus(404);

    // ensure no progress record was created inadvertently
    $this->assertDatabaseMissing('learning_progresses', [
        'student_schedule_id' => $schedule->id,
        'term' => '2025B',
    ]);
});

test('creates learning progress record if not exists', function () {
    $schedule = StudentSchedule::factory()->create();

    // attach at least one course item for the term
    $courseClass = CourseClass::factory()
        ->for(
            Course::factory()->state(['term' => '2025B'])
        )
        ->create();
    $schedule->items()->create(['course_id' => $courseClass->course_id, 'course_class_id' => $courseClass->id]);

    // Ensure no learning progress exists
    $this->assertDatabaseMissing('learning_progresses', [
        'student_schedule_id' => $schedule->id,
        'term' => '2025B',
    ]);

    // Visit the page
    $response = $this->get(route('learning-progress.show', [
        'schedule' => $schedule,
        'term' => '2025B',
    ]));

    // Should create a new record
    $this->assertDatabaseHas('learning_progresses', [
        'student_schedule_id' => $schedule->id,
        'term' => '2025B',
    ]);
    $response->assertStatus(200);
});

test('can update learning progress', function () {
    $schedule = StudentSchedule::factory()->create();
    $progress = LearningProgress::factory()->create([
        'student_schedule_id' => $schedule->id,
        'term' => '2025B',
    ]);

    $updateData = [
        'progress' => [
            '1' => [
                '1' => ['video' => '1', 'textbook' => '1'],
                '2' => ['video' => '1'],
            ],
        ],
        'notes' => [
            '1' => [
                '1' => 'Test note for week 1',
            ],
        ],
    ];

    $response = $this->put(route('learning-progress.update', [
        'schedule' => $schedule,
        'term' => '2025B',
    ]), $updateData);

    $response->assertRedirect();

    // Check database for updated progress
    $updated = LearningProgress::find($progress->id);
    $this->assertNotNull($updated->progress[1][1]['video']);
    $this->assertNotNull($updated->notes[1][1]);
});

test('learning progress has correct structure', function () {
    $schedule = StudentSchedule::factory()->create();
    $progress = LearningProgress::factory()->create([
        'student_schedule_id' => $schedule->id,
        'term' => '2025B',
        'progress' => [
            '1' => [
                '1' => ['video' => true, 'textbook' => false],
            ],
        ],
        'notes' => [
            '1' => [
                '1' => 'Sample note',
            ],
        ],
    ]);

    $this->assertIsArray($progress->progress);
    $this->assertIsArray($progress->notes);
    $this->assertTrue($progress->progress[1][1]['video']);
    $this->assertFalse($progress->progress[1][1]['textbook']);
    $this->assertEquals('Sample note', $progress->notes[1][1]);
});

test('viewmodel reflects marked progress and note after update-then-reload', function () {
    $schedule = StudentSchedule::factory()->create();

    $courseClass = CourseClass::factory()
        ->for(
            Course::factory()->state(['term' => '2025B'])
        )
        ->create();
    $schedule->items()->create(['course_id' => $courseClass->course_id, 'course_class_id' => $courseClass->id]);
    $courseId = $courseClass->course_id;

    LearningProgress::factory()->create([
        'student_schedule_id' => $schedule->id,
        'term' => '2025B',
        'progress' => [],
        'notes' => [],
    ]);

    // mark video + textbook complete for week 1 and add a note
    $updateData = [
        'progress' => [
            (string) $courseId => [
                '1' => ['video' => '1', 'textbook' => '1'],
            ],
        ],
        'notes' => [
            (string) $courseId => [
                '1' => 'Watched lecture and read chapter 1',
            ],
        ],
    ];

    $this->put(route('learning-progress.update', [
        'schedule' => $schedule,
        'term' => '2025B',
    ]), $updateData)->assertRedirect();

    $response = $this->get(route('learning-progress.show', [
        'schedule' => $schedule,
        'term' => '2025B',
    ]));

    $response->assertStatus(200);
    $viewModel = $response->viewData('viewModel');

    expect($viewModel->isProgressComplete($courseId, 1))->toBeTrue();
    expect($viewModel->isVideoComplete($courseId, 1))->toBeTrue();
    expect($viewModel->isTextbookComplete($courseId, 1))->toBeTrue();
    expect($viewModel->getNote($courseId, 1))->toBe('Watched lecture and read chapter 1');

    // an untouched week should remain incomplete with an empty note
    expect($viewModel->isProgressComplete($courseId, 2))->toBeFalse();
    expect($viewModel->getNote($courseId, 2))->toBe('');

    $response->assertSee('Watched lecture and read chapter 1');
});

test('unique constraint on student_schedule_id and term', function () {
    $schedule = StudentSchedule::factory()->create();

    LearningProgress::factory()->create([
        'student_schedule_id' => $schedule->id,
        'term' => '2025B',
    ]);

    // Try to create duplicate
    $this->expectException(QueryException::class);

    LearningProgress::factory()->create([
        'student_schedule_id' => $schedule->id,
        'term' => '2025B',
    ]);
});
