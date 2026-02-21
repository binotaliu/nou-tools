<?php

use App\Models\Course;
use App\Models\CourseClass;
use App\Models\LearningProgress;
use App\Models\StudentSchedule;
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

    $schedule->items()->create(['course_class_id' => $courseClass->id]);

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
    $schedule->items()->create(['course_class_id' => $courseClass->id]);

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

test('unique constraint on student_schedule_id and term', function () {
    $schedule = StudentSchedule::factory()->create();

    LearningProgress::factory()->create([
        'student_schedule_id' => $schedule->id,
        'term' => '2025B',
    ]);

    // Try to create duplicate
    $this->expectException(\Illuminate\Database\QueryException::class);

    LearningProgress::factory()->create([
        'student_schedule_id' => $schedule->id,
        'term' => '2025B',
    ]);
});
