<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\StudyActivityVerb;
use App\Enums\StudyTimerMode;
use App\Models\StudentSchedule;
use App\Models\StudyRoomSession;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StudyRoomSession>
 */
final class StudyRoomSessionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $startedAt = $this->faker->dateTimeBetween('-1 day', '-30 minutes');
        $focusSeconds = 25 * 60;

        return [
            'student_schedule_id' => StudentSchedule::factory(),
            'subject_label' => $this->faker->words(2, true),
            'activity_verb' => StudyActivityVerb::Review,
            'timer_mode' => StudyTimerMode::Pomodoro,
            'started_at' => $startedAt,
            'ended_at' => (clone $startedAt)->modify("+{$focusSeconds} seconds"),
            'focus_seconds' => $focusSeconds,
            'was_completed' => true,
        ];
    }
}
