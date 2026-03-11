<?php

namespace Database\Factories;

use App\Models\LearningProgress;
use App\Models\StudentSchedule;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LearningProgress>
 */
class LearningProgressFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'student_schedule_id' => StudentSchedule::factory(),
            'term' => '2025B',
            'progress' => [],
            'notes' => [],
        ];
    }
}
