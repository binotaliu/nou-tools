<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\StudentSchedule;
use App\Models\StudyRoomProfile;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StudyRoomProfile>
 */
final class StudyRoomProfileFactory extends Factory
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
            'nickname' => $this->faker->firstName(),
            'emoji' => $this->faker->randomElement(config('study-room.emojis')),
            'play_sound_on_timer_end' => true,
            'notify_on_timer_end' => false,
        ];
    }
}
