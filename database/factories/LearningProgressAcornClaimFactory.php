<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\LearningProgressAcornClaim;
use App\Models\StudentSchedule;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LearningProgressAcornClaim>
 */
final class LearningProgressAcornClaimFactory extends Factory
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
            'item_key' => '1:w1:video',
        ];
    }
}
