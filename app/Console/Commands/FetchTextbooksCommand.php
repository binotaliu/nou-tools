<?php

namespace App\Console\Commands;

use App\Models\Course;
use App\Models\Textbook;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class FetchTextbooksCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'course:fetch-textbooks {term : The term to fetch (e.g. 2025B)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fetch textbook information from Google Sheets CSV and save to database';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $term = $this->argument('term');

        if (! is_string($term) || ! preg_match('/^\d{4}[ABC]$/', $term)) {
            $this->error('Invalid term format. Expected format: 2025B (year + A/B/C)');

            return self::FAILURE;
        }

        $rocTerm = $this->convertToRocTerm($term);

        $this->info("Fetching textbooks for term: {$term} (ROC: {$rocTerm})");

        $csv = $this->fetchCsv();

        if ($csv === null) {
            $this->error('Failed to fetch CSV data');

            return self::FAILURE;
        }

        $rows = $this->parseCsv($csv);

        if (empty($rows)) {
            $this->warn('No data found in CSV');

            return self::FAILURE;
        }

        $totalProcessed = 0;
        $totalCreated = 0;
        $totalUpdated = 0;
        $totalSkipped = 0;

        foreach ($rows as $row) {
            if ($row['term'] !== $rocTerm) {
                continue;
            }

            $totalProcessed++;

            $courseName = $this->normalizeCourseName($row['book_title']);

            $course = Course::query()
                ->where('term', $term)
                ->get()
                ->first(function (Course $c) use ($courseName) {
                    return $this->normalizeCourseName($c->name) === $courseName;
                });

            if (! $course) {
                $this->warn("Course not found: {$row['book_title']} (normalized: {$courseName})");
                $totalSkipped++;

                continue;
            }

            $existing = Textbook::query()
                ->where('course_id', $course->id)
                ->where('term', $term)
                ->first();

            if ($existing) {
                $existing->update([
                    'book_title' => $row['book_title'],
                    'edition' => $row['edition'],
                    'price_info' => $row['price_info'],
                    'reference_url' => $row['reference_url'],
                ]);
                $totalUpdated++;
            } else {
                Textbook::create([
                    'course_id' => $course->id,
                    'term' => $term,
                    'book_title' => $row['book_title'],
                    'edition' => $row['edition'],
                    'price_info' => $row['price_info'],
                    'reference_url' => $row['reference_url'],
                ]);
                $totalCreated++;
            }
        }

        $this->info("Done! Processed: {$totalProcessed}, Created: {$totalCreated}, Updated: {$totalUpdated}, Skipped: {$totalSkipped}");

        return self::SUCCESS;
    }

    /**
     * Convert calendar term to ROC term format.
     *
     * Example: 2025B -> 114下
     */
    private function convertToRocTerm(string $term): string
    {
        $calendarYear = (int) substr($term, 0, 4);
        $termCode = substr($term, 4, 1);

        $rocYear = $calendarYear - 1911;

        $termMap = [
            'A' => '上',
            'B' => '下',
            'C' => '暑',
        ];

        return $rocYear.$termMap[$termCode];
    }

    /**
     * Fetch CSV from Google Sheets.
     */
    private function fetchCsv(): ?string
    {
        $url = 'https://docs.google.com/spreadsheets/d/e/2PACX-1vSo1ll-1JUWB2TTlcrB6ONlRnvD8_S7qZQdN6jDO3upA93woODrAoYOzmsvEsbFgqYlfMAzSHl2ryZG/pub?gid=0&single=true&output=csv';

        try {
            $response = Http::timeout(30)->get($url);

            if (! $response->successful()) {
                return null;
            }

            return $response->body();
        } catch (\Exception $e) {
            $this->error("Error fetching CSV: {$e->getMessage()}");

            return null;
        }
    }

    /**
     * Parse CSV content into array of rows.
     *
     * @return array<int, array<string, string>>
     */
    private function parseCsv(string $csv): array
    {
        $lines = explode("\n", $csv);

        if (empty($lines)) {
            return [];
        }

        // Skip header row
        array_shift($lines);

        $rows = [];

        foreach ($lines as $line) {
            $line = trim($line);

            if (empty($line)) {
                continue;
            }

            $fields = str_getcsv($line);

            if (count($fields) < 6) {
                continue;
            }

            $rows[] = [
                'term' => $fields[0],
                'book_title' => $fields[2],
                'edition' => $fields[3],
                'price_info' => $fields[4],
                'reference_url' => $fields[5],
            ];
        }

        return $rows;
    }

    /**
     * Normalize course name for comparison.
     */
    private function normalizeCourseName(string $name): string
    {
        // Remove all whitespace
        $name = preg_replace('/\s+/', '', $name);

        // Convert full-width characters to half-width
        $name = mb_convert_kana($name, 'as', 'UTF-8');

        // Convert to lowercase for case-insensitive comparison
        $name = mb_strtolower($name, 'UTF-8');

        $name = str_replace(['臺'], ['台'], $name);

        return $name ?? '';
    }
}
