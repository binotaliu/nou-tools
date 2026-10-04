<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use NouTools\Domains\Courses\Actions\ImportProgramCourses;
use NouTools\Domains\Courses\Actions\ParseNouProgramCourses;

final class FetchProgramCoursesCommand extends Command
{
    protected $signature = 'program:fetch {term? : The term the pages describe (defaults to the current semester)}';

    protected $description = 'Fetch 專班 courses and classes from the NOU video-class region pages';

    public function handle(ParseNouProgramCourses $parseNouProgramCourses, ImportProgramCourses $importProgramCourses): int
    {
        $term = $this->argument('term') ?? config('app.current_semester');

        if (! is_string($term) || ! preg_match('/^\d{4}[ABC]$/', $term)) {
            $this->error('Invalid term format. Expected format: 2025B (year + A/B/C)');

            return self::FAILURE;
        }

        $this->info("Fetching 專班 for term: {$term}");

        $programsByRegion = [];
        $complete = true;

        foreach ((array) config('special_programs.regions') as $region => $config) {
            $response = Http::timeout(30)->get($config['url']);

            if (! $response->successful()) {
                $this->warn("Failed to fetch {$config['url']}, skipping...");
                $complete = false;

                continue;
            }

            $programs = $parseNouProgramCourses($response->body());

            if ($programs === []) {
                $this->warn("No 專班 found on {$config['url']}");
                $complete = false;
            }

            $programsByRegion[$region] = $programs;
            $this->info("  {$config['label']}: ".count($programs).' 專班');
        }

        $importProgramCourses($term, $programsByRegion, removeStale: $complete);

        if (! $complete) {
            $this->warn('Some pages failed or were empty, so nothing was removed.');
        }

        $this->info('Done!');

        return $complete ? self::SUCCESS : self::FAILURE;
    }
}
