<?php

namespace App\Console\Commands;

use App\Services\ExamScheduleService;
use Illuminate\Console\Command;

class ImportExamSchedules extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'exam:import {term : The term to import (e.g. 2025B)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import exam schedules from JSON file';

    /**
     * Execute the console command.
     */
    public function handle(ExamScheduleService $service): int
    {
        $term = $this->argument('term');

        if (! is_string($term)) {
            $this->error('Invalid term argument');

            return self::FAILURE;
        }

        try {
            $this->info("Importing exam schedules for term: {$term}");

            $result = $service->import($term);

            $this->info('Import completed successfully!');
            $this->info("Matched: {$result['success']} courses");

            if ($result['failed'] > 0) {
                $this->warn("Failed to match: {$result['failed']} courses");
            }

            return self::SUCCESS;
        } catch (\RuntimeException $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }
    }
}
