<?php

namespace Database\Factories;

use App\Enums\CourseClassType;
use App\Models\Course;
use App\Models\CourseClass;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\CourseClass>
 */
class CourseClassFactory extends Factory
{
    protected $model = CourseClass::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $type = fake()->randomElement(CourseClassType::cases());
        $timeSlot = $type->defaultTimeSlot();

        return [
            'course_id' => Course::factory(),
            'code' => 'zzz'.fake()->numerify('###'),
            'type' => $type,
            'start_time' => $timeSlot ? $timeSlot['start'] : '09:00',
            'end_time' => $timeSlot ? $timeSlot['end'] : '10:50',
            'teacher_name' => fake()->name().'老師',
            'link' => fake()->url(),
        ];
    }
}
