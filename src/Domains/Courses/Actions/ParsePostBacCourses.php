<?php

declare(strict_types=1);

namespace NouTools\Domains\Courses\Actions;

use Illuminate\Support\Facades\Date;

/**
 * Reads the post-bac (學士後) classes from `resources/data/post-bac.json` and
 * returns them in the shape ParseNouProgramCourses produces, so
 * ImportProgramCourses treats each class as a 專班. The file is keyed by term;
 * every course meets in the evening (夜間班), 19:00-20:50.
 */
final readonly class ParsePostBacCourses
{
    public const string START_TIME = '19:00';

    public const string END_TIME = '20:50';

    /**
     * @return array<int, array{name: string, courses: array<int, array{name: string, start_time: string, end_time: string, teacher_name: string, link: string, dates: array<int, string>, schedule_time_overrides: array<int, array{start_time: string, end_time: string}>}>}>
     */
    public function __invoke(string $json, string $term): array
    {
        /** @var array<string, array{courses?: array<int, array<string, mixed>>, classes?: array<int, array<string, mixed>>}>|null $data */
        $data = json_decode($json, true);

        $termData = is_array($data) ? ($data[$term] ?? null) : null;

        if (! is_array($termData)) {
            return [];
        }

        $courses = [];

        foreach ($termData['courses'] ?? [] as $course) {
            $courses[(string) $course['id']] = $course;
        }

        $programs = [];

        foreach ($termData['classes'] ?? [] as $class) {
            $programCourses = [];

            foreach ($class['courses'] as $courseId) {
                $course = $courses[(string) $courseId] ?? null;

                if ($course === null) {
                    continue;
                }

                $programCourses[] = [
                    'name' => $course['name'],
                    'start_time' => self::START_TIME,
                    'end_time' => self::END_TIME,
                    'teacher_name' => '',
                    'link' => '',
                    'dates' => array_map(
                        fn (string $date): string => Date::parse($date)->format('m/d'),
                        $course['dates'],
                    ),
                    'schedule_time_overrides' => [],
                ];
            }

            $programs[] = ['name' => $class['name'], 'courses' => $programCourses];
        }

        return $programs;
    }
}
