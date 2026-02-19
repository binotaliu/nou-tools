<?php

namespace Database\Factories;

use App\Models\ClassSchedule;
use App\Models\CourseClass;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ClassSchedule>
 */
class ClassScheduleFactory extends Factory
{
    protected $model = ClassSchedule::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'class_id' => CourseClass::factory(),
            'date' => fake()->dateTimeBetween('+1 week', '+6 months'),
            'start_time' => null,
            'end_time' => null,
        ];
    }

    /**
     * Set a time override for this schedule.
     */
    public function withTimeOverride(string $startTime, string $endTime): static
    {
        return $this->state(fn (array $attributes): array => [
            'start_time' => $startTime,
            'end_time' => $endTime,
        ]);
    }
}
