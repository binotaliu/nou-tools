<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use NouTools\Domains\Schedules\Actions\ImportCourseSelectSimulation;

final class ImportCourseSelectSimulationCommand extends Command
{
    protected $signature = 'course-select-sim:import {term? : Term to import (e.g. 2025B), defaults to every term present in the file}';

    protected $description = 'Import 選課注意事項 video-session and exam data as tentative course classes and exam schedules';

    public function handle(ImportCourseSelectSimulation $importCourseSelectSimulation): int
    {
        $term = $this->argument('term');

        $result = $importCourseSelectSimulation(is_string($term) ? $term : null);

        $this->info("Imported {$result['classes']} tentative class(es) and {$result['exams']} exam schedule(s) across {$result['terms']} term(s).");

        return self::SUCCESS;
    }
}
