<?php

namespace App\Services;

use App\Models\Course;
use Illuminate\Support\Facades\File;

class ExamScheduleService
{
    /**
     * Normalize course name for matching.
     * Removes or normalizes special characters and spaces.
     */
    public function normalizeName(string $name): string
    {
        // Remove or replace special characters: （）：～, ASCII variants, dashes, and spaces.
        $normalized = trim($name);
        $normalized = str_replace(['（', '）', '(', ')', '：', ':', '～', '~', '—', '－', '-', '–', '　', ' '], '', $normalized);

        return $normalized;
    }

    /**
     * Import exam schedules from JSON file.
     *
     * @param  string  $term  The term code (e.g., '2025B')
     * @return array{success: int, failed: int}
     */
    public function import(string $term): array
    {
        $path = resource_path('data/exams.json');

        if (! File::exists($path)) {
            throw new \RuntimeException("Exam schedule file not found at {$path}");
        }

        $content = File::get($path);
        $data = json_decode($content, true);

        if (! isset($data[$term])) {
            throw new \RuntimeException("No exam schedule data found for term: {$term}");
        }

        $termData = $data[$term];
        $dates = $termData['dates'];
        $slots = $termData['slots'];

        $success = 0;
        $failed = 0;

        foreach ($slots as $slot) {
            $time = $slot['time'];
            [$startTime, $endTime] = explode('-', $time);

            // Process Saturday courses
            foreach ($slot['saturday'] as $course) {
                if ($this->updateCourseExamSchedule(
                    $course['title'],
                    $term,
                    $dates['saturday']['midterm'],
                    $dates['saturday']['final'],
                    $startTime,
                    $endTime
                )) {
                    $success++;
                } else {
                    $failed++;
                }
            }

            // Process Sunday courses
            foreach ($slot['sunday'] as $course) {
                if ($this->updateCourseExamSchedule(
                    $course['title'],
                    $term,
                    $dates['sunday']['midterm'],
                    $dates['sunday']['final'],
                    $startTime,
                    $endTime
                )) {
                    $success++;
                } else {
                    $failed++;
                }
            }
        }

        return [
            'success' => $success,
            'failed' => $failed,
        ];
    }

    /**
     * Update a single course with exam schedule information.
     */
    private function updateCourseExamSchedule(
        string $title,
        string $term,
        string $midtermDate,
        string $finalDate,
        string $startTime,
        string $endTime
    ): bool {
        $normalizedTitle = $this->normalizeName($title);

        // Find course with normalized name matching
        $course = Course::query()
            ->where('term', $term)
            ->where(function ($query) use ($normalizedTitle) {
                $query->whereRaw(
                    "REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(name, '（', ''), '）', ''), '(', ''), ')', ''), '：', ''), ':', ''), '～', ''), '~', ''), '—', ''), '－', ''), '-', ''), '–', ''), '　', ''), ' ', '') = ?",
                    [$normalizedTitle]
                );
            })
            ->first();

        $course->update([
            'midterm_date' => $midtermDate,
            'final_date' => $finalDate,
            'exam_time_start' => $startTime,
            'exam_time_end' => $endTime,
        ]);

        return true;
    }
}
