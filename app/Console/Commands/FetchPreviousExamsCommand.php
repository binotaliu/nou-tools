<?php

namespace App\Console\Commands;

use App\Models\PreviousExam;
use Illuminate\Console\Command;

class FetchPreviousExamsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'course:fetch-previous-exams';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fetch previous exam information from JSON and save to database';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $path = resource_path('data/previous-exams.json');

        if (! file_exists($path)) {
            $this->error("File not found: {$path}");

            return self::FAILURE;
        }

        $json = file_get_contents($path);
        $data = json_decode($json, true);

        if ($data === null) {
            $this->error('Failed to parse JSON file');

            return self::FAILURE;
        }

        if (! isset($data['data']) || ! is_array($data['data'])) {
            $this->error('Invalid JSON structure: missing "data" key');

            return self::FAILURE;
        }

        $rows = $data['data'];

        if (empty($rows)) {
            $this->warn('No data found in JSON');

            return self::FAILURE;
        }

        $count = count($rows);
        $this->info("Found {$count} exam records");

        $totalProcessed = 0;
        $totalCreated = 0;
        $totalUpdated = 0;

        foreach ($rows as $row) {
            if (empty($row['CRS_NAME']) || empty($row['CRSNO'])) {
                continue;
            }

            $totalProcessed++;

            $term = $row['AYEAR_SMS'] ?? null;

            $existing = PreviousExam::query()
                ->where('course_no', $row['CRSNO'])
                ->where('term', $term)
                ->first();

            $data = [
                'course_name' => $row['CRS_NAME'],
                'course_no' => $row['CRSNO'],
                'term' => $row['AYEAR_SMS'] ?? null,
                'midterm_reference_primary' => $row['EXA_REFANS11'] ?? null,
                'midterm_reference_secondary' => $row['EXA_REFANS12'] ?? null,
                'final_reference_primary' => $row['EXA_REFANS21'] ?? null,
                'final_reference_secondary' => $row['EXA_REFANS22'] ?? null,
            ];

            if ($existing) {
                $existing->update($data);
                $totalUpdated++;
            } else {
                PreviousExam::create($data);
                $totalCreated++;
            }
        }

        $this->info("Done! Processed: {$totalProcessed}, Created: {$totalCreated}, Updated: {$totalUpdated}");

        return self::SUCCESS;
    }
}
