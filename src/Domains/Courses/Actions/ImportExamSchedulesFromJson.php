<?php

namespace NouTools\Domains\Courses\Actions;

use App\Models\Course;
use Illuminate\Support\Facades\File;

final class ImportExamSchedulesFromJson
{
    public function __invoke(string $term): array
    {
        $path = resource_path('data/exams.json');

        if (! File::exists($path)) {
            throw new \RuntimeException("Exam schedule file not found at {$path}");
        }

        $data = json_decode(File::get($path), true);

        if (! isset($data[$term])) {
            throw new \RuntimeException("No exam schedule data found for term: {$term}");
        }

        $termData = $data[$term];
        $dates = $termData['dates'];
        $slots = $termData['slots'];
        $success = 0;
        $failed = 0;

        foreach ($slots as $slot) {
            [$startTime, $endTime] = explode('-', $slot['time']);

            foreach ($slot['saturday'] as $course) {
                $this->updateCourseExamSchedule(
                    title: $course['title'],
                    term: $term,
                    midtermDate: $dates['saturday']['midterm'],
                    finalDate: $dates['saturday']['final'],
                    startTime: $startTime,
                    endTime: $endTime,
                    success: $success,
                    failed: $failed,
                );
            }

            foreach ($slot['sunday'] as $course) {
                $this->updateCourseExamSchedule(
                    title: $course['title'],
                    term: $term,
                    midtermDate: $dates['sunday']['midterm'],
                    finalDate: $dates['sunday']['final'],
                    startTime: $startTime,
                    endTime: $endTime,
                    success: $success,
                    failed: $failed,
                );
            }
        }

        return ['success' => $success, 'failed' => $failed];
    }

    private function updateCourseExamSchedule(string $title, string $term, string $midtermDate, string $finalDate, string $startTime, string $endTime, int &$success, int &$failed): void
    {
        $normalizedTitle = $this->normalizeName($title);
        $course = Course::query()
            ->where('term', $term)
            ->where(function ($query) use ($normalizedTitle) {
                $query->whereRaw(
                    "REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(name, '（', ''), '）', ''), '(', ''), ')', ''), '：', ''), ':', ''), '～', ''), '~', ''), '—', ''), '－', ''), '-', ''), '–', ''), '　', ''), ' ', '') = ?",
                    [$normalizedTitle],
                );
            })
            ->first();

        if (! $course) {
            $failed++;

            return;
        }

        $course->midterm_date = $midtermDate;
        $course->final_date = $finalDate;
        $course->exam_time_start = $startTime;
        $course->exam_time_end = $endTime;
        $course->saveOrFail();
        $success++;
    }

    public function normalizeName(string $name): string
    {
        return str_replace(['（', '）', '(', ')', '：', ':', '～', '~', '—', '－', '-', '–', '　', ' '], '', trim($name));
    }
}
