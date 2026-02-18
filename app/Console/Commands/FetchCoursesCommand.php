<?php

namespace App\Console\Commands;

use App\Enums\CourseClassType;
use App\Models\Course;
use App\Models\CourseClass;
use App\Services\NouCourseParser;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class FetchCoursesCommand extends Command
{
    protected $signature = 'course:fetch {term : The term to fetch (e.g. 2025B)}';

    protected $description = 'Fetch course data from NOU website and save to database';

    /**
     * @var array<string, CourseClassType>
     */
    private array $sources = [
        'https://vc.nou.edu.tw/vc1/' => CourseClassType::Morning,
        'https://vc.nou.edu.tw/vc2/' => CourseClassType::Afternoon,
        'https://vc.nou.edu.tw/vc3/' => CourseClassType::Evening,
        'https://vc.nou.edu.tw/vc4/' => CourseClassType::FullRemote,
    ];

    public function handle(NouCourseParser $parser): int
    {
        $term = $this->argument('term');

        if (! is_string($term) || ! preg_match('/^\d{4}[ABC]$/', $term)) {
            $this->error('Invalid term format. Expected format: 2025B (year + A/B/C)');

            return self::FAILURE;
        }

        $year = $this->extractYear($term);

        $this->info("Fetching courses for term: {$term}");

        $totalCourses = 0;
        $totalClasses = 0;

        foreach ($this->sources as $url => $type) {
            $this->info("Fetching {$type->label()} from {$url}...");

            $html = $this->fetchHtml($url);

            if ($html === null) {
                $this->warn("Failed to fetch {$url}, skipping...");

                continue;
            }

            $courses = $parser->parse($html, $type);

            foreach ($courses as $courseData) {
                $course = Course::query()->firstOrCreate(
                    ['name' => $courseData['name'], 'term' => $term],
                );

                foreach ($courseData['classes'] as $classData) {
                    $code = strtoupper($classData['code']);
                    $type = $classData['type']->value;

                    $courseClass = CourseClass::query()->firstOrCreate(
                        [
                            'course_id' => $course->id,
                            'code' => $code,
                            'type' => $type,
                        ],
                        [
                            'start_time' => $classData['start_time'],
                            'end_time' => $classData['end_time'],
                            'teacher_name' => $classData['teacher_name'],
                            'link' => $classData['link'],
                        ],
                    );

                    // Update existing record with latest data
                    $courseClass->update([
                        'start_time' => $classData['start_time'],
                        'end_time' => $classData['end_time'],
                        'teacher_name' => $classData['teacher_name'],
                        'link' => $classData['link'],
                    ]);

                    foreach ($classData['dates'] as $dateString) {
                        $date = $this->parseDate($dateString, $year);

                        if ($date !== null) {
                            $dateStr = $date->format('Y-m-d');
                            // Use upsert for better handling of the date field
                            DB::table('class_schedules')->upsert(
                                [
                                    'class_id' => $courseClass->id,
                                    'date' => $dateStr,
                                    'created_at' => Carbon::now(),
                                    'updated_at' => Carbon::now(),
                                ],
                                ['class_id', 'date'],
                                ['updated_at'],
                            );
                        }
                    }

                    $totalClasses++;
                }

                $totalCourses++;
            }

            $this->info('  Parsed '.count($courses).' courses');
        }

        $this->info("Done! Total: {$totalCourses} course entries, {$totalClasses} classes");

        return self::SUCCESS;
    }

    /**
     * Fetch HTML content from a URL.
     */
    protected function fetchHtml(string $url): ?string
    {
        $response = Http::timeout(30)->get($url);

        if (! $response->successful()) {
            return null;
        }

        return $response->body();
    }

    /**
     * Extract the ROC year from the term and determine the calendar year.
     */
    private function extractYear(string $term): int
    {
        $yearPart = (int) substr($term, 0, 4);
        $semester = substr($term, 4, 1);

        if ($semester === 'A') {
            return $yearPart;
        }

        return $yearPart + 1;
    }

    /**
     * Parse a date string like "03/09" into a Carbon date for the given year.
     */
    private function parseDate(string $dateString, int $year): ?Carbon
    {
        $parts = explode('/', $dateString);

        if (count($parts) !== 2) {
            return null;
        }

        $month = (int) $parts[0];
        $day = (int) $parts[1];

        if ($month < 1 || $month > 12 || $day < 1 || $day > 31) {
            return null;
        }

        return Carbon::create($year, $month, $day);
    }
}
