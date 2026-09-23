<?php

use App\Models\Course;
use App\Models\Textbook;
use Illuminate\Support\Facades\Http;

it('fails with invalid term format', function () {
    $this->artisan('course:fetch-textbooks', ['term' => 'invalid'])
        ->expectsOutput('Invalid term format. Expected format: 2025B (year + A/B/C)')
        ->assertExitCode(1);
});

it('handles a failure when CSV cannot be fetched', function () {
    Http::fake(['*' => Http::response('', 500)]);

    $this->artisan('course:fetch-textbooks', ['term' => '2025B'])
        ->expectsOutput('Fetching textbooks for term: 2025B (ROC: 114下)')
        ->expectsOutput('Failed to fetch CSV data')
        ->assertExitCode(1);
});

it('reports counts and updates/creates correctly', function () {
    // set up a course and an existing textbook to trigger update logic
    $course = Course::factory()->create([
        'term' => '2025B',
        'name' => 'My Course',
    ]);

    $existing = Textbook::factory()->create([
        'course_id' => $course->id,
        'term' => '2025B',
        'book_title' => 'Old Title',
        'edition' => '1st',
        'price_info' => '100',
        'reference_url' => 'https://old.example',
    ]);

    $rocTerm = '114下';
    $csvLines = [
        'term,dept,book_title,edition,price_info,reference_url',
        "{$rocTerm},X,My Course,2nd,200,https://new.example",
        "{$rocTerm},X,Other Course,1st,150,https://other.example",
    ];

    Http::fake(['*' => Http::response(implode("\n", $csvLines), 200)]);

    $this->artisan('course:fetch-textbooks', ['term' => '2025B'])
        ->expectsOutput("Fetching textbooks for term: 2025B (ROC: {$rocTerm})")
        ->expectsOutput('Done! Processed: 2, Created: 0, Updated: 1, Skipped: 1')
        ->assertExitCode(0);

    expect(Textbook::query()->where('id', $existing->id)->first()->edition)->toBe('2nd');
    $this->assertDatabaseMissing('textbooks', ['book_title' => 'Other Course']);
});
