<?php

namespace Database\Seeders;

use App\Models\ClassSchedule;
use App\Models\Course;
use App\Models\CourseClass;
use Illuminate\Database\Seeder;

class TestScheduleSeeder extends Seeder
{
    public function run(): void
    {
        // Create test courses
        $math = Course::create([
            'name' => '初級數學',
            'term' => '2026年春季',
        ]);

        $english = Course::create([
            'name' => '英語會話',
            'term' => '2026年春季',
        ]);

        $physics = Course::create([
            'name' => '基礎物理',
            'term' => '2026年春季',
        ]);

        // Create course classes for Math
        $mathA = CourseClass::create([
            'course_id' => $math->id,
            'code' => 'MATH101-A',
            'type' => 'morning',
            'start_time' => '09:00',
            'end_time' => '10:30',
            'teacher_name' => '王老師',
            'link' => 'https://meet.google.com/example',
        ]);

        $mathB = CourseClass::create([
            'course_id' => $math->id,
            'code' => 'MATH101-B',
            'type' => 'morning',
            'start_time' => '10:30',
            'end_time' => '12:00',
            'teacher_name' => '李老師',
            'link' => null,
        ]);

        // Create course classes for English
        $englishA = CourseClass::create([
            'course_id' => $english->id,
            'code' => 'ENG102-A',
            'type' => 'afternoon',
            'start_time' => '13:30',
            'end_time' => '15:00',
            'teacher_name' => 'Mr. Smith',
            'link' => 'https://zoom.us/example',
        ]);

        // Create course classes for Physics
        $physicsA = CourseClass::create([
            'course_id' => $physics->id,
            'code' => 'PHY103-A',
            'type' => 'afternoon',
            'start_time' => '15:30',
            'end_time' => '17:00',
            'teacher_name' => '陳教授',
            'link' => null,
        ]);

        // Add schedules for test data
        $today = now();
        for ($i = 0; $i < 10; $i++) {
            ClassSchedule::create([
                'class_id' => $mathA->id,
                'date' => $today->addDays($i * 7),
            ]);

            ClassSchedule::create([
                'class_id' => $englishA->id,
                'date' => $today->addDays($i * 7 + 2),
            ]);

            ClassSchedule::create([
                'class_id' => $physicsA->id,
                'date' => $today->addDays($i * 7 + 4),
            ]);
        }
    }
}
