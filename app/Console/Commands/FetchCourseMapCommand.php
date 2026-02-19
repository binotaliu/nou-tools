<?php

namespace App\Console\Commands;

use App\Models\Course;
use App\Services\CourseMapParser;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class FetchCourseMapCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'course:fetch-map {term : The term to fetch (e.g. 2025B)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fetch course information from course map website (pass term like 2025B)';

    /**
     * The departments available in the course map.
     *
     * @var array<string>
     */
    private array $departments = [
        '',  // All departments (empty)
        '人文學系',
        '社會科學系',
        '商學系',
        '公共行政學系',
        '生活科學系',
        '管理與資訊學系',
        '通識博雅教育中心',
    ];

    /**
     * Execute the console command.
     */
    public function handle(CourseMapParser $parser): int
    {
        $term = $this->argument('term');

        // Expect term like 2025B (4-digit calendar year + A/B/C)
        if (! is_string($term) || ! preg_match('/^\d{4}[ABC]$/', $term)) {
            $this->error('Invalid term. Expected format: 2025B (year + A/B/C)');

            return self::FAILURE;
        }

        // convert calendar term -> ROC year + semester number for the external site
        $calendarYear = (int) substr($term, 0, 4);
        $termCode = substr($term, 4, 1);
        $rocYear = $calendarYear - 1911;
        $semesterMap = ['A' => '1', 'B' => '2', 'C' => '3'];
        $semesterNumber = $semesterMap[$termCode];

        $this->info("Fetching course map for term: {$term} (ROC {$rocYear}, semester {$semesterNumber})");

        $totalCourses = 0;
        $createdCourses = 0;
        $updatedCourses = 0;

        foreach ($this->departments as $dept) {
            $deptName = empty($dept) ? 'All departments' : $dept;
            $this->info("Fetching from {$deptName}...");

            $html = $this->fetchCourseMapHtml((string) $rocYear, $semesterNumber, $dept);

            if ($html === null) {
                $this->warn("Failed to fetch course map for {$deptName}");

                continue;
            }

            $courses = $parser->parse($html);

            foreach ($courses as $courseData) {
                $existing = Course::query()
                    ->where('name', $courseData['name'])
                    ->where('term', $term)
                    ->first();

                if ($existing) {
                    $existing->update([
                        'description_url' => $courseData['description_url'],
                        'credit_type' => $courseData['credit_type'],
                        'credits' => $courseData['credits'],
                        'department' => $courseData['department'],
                        'in_person_class_type' => $courseData['in_person_class_type'],
                        'media' => $courseData['media'],
                        'multimedia_url' => $courseData['multimedia_url'],
                        'nature' => $courseData['nature'],
                    ]);
                    $updatedCourses++;
                } else {
                    Course::create([
                        'name' => $courseData['name'],
                        'term' => $term,
                        'description_url' => $courseData['description_url'],
                        'credit_type' => $courseData['credit_type'],
                        'credits' => $courseData['credits'],
                        'department' => $courseData['department'],
                        'in_person_class_type' => $courseData['in_person_class_type'],
                        'media' => $courseData['media'],
                        'multimedia_url' => $courseData['multimedia_url'],
                        'nature' => $courseData['nature'],
                    ]);
                    $createdCourses++;
                }

                $totalCourses++;
            }

            $this->info("  Parsed {$totalCourses} courses so far");
        }

        $this->info("Done! Created: {$createdCourses}, Updated: {$updatedCourses}");

        return self::SUCCESS;
    }

    /**
     * Fetch course map HTML from the website.
     */
    private function fetchCourseMapHtml(string $rocYear, string $semester, string $dept): ?string
    {
        $url = 'https://coursemap.nou.edu.tw/sp.asp?xdurl=mp1ap/searchCourse.asp'
            .'&mp=1&ctNode=1051&pageSize=999&nowpage=1&submitTask=s1'
            .'&skeywd=%'
            .'&year1='.$rocYear
            .'&semester1='.$semester
            .'&dept1='.urlencode($dept)
            .'&year2=&semester2=&dept2=&mediaNote2=&FOS2='
            .'&year8=&semester8=&FOS8=';

        try {
            $response = Http::timeout(30)->get($url);

            if (! $response->successful()) {
                return null;
            }

            return $response->body();
        } catch (\Exception $e) {
            $this->warn("Error fetching {$url}: ".$e->getMessage());

            return null;
        }
    }
}
