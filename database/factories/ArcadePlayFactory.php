<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\ArcadeGame;
use App\Models\ArcadePlay;
use App\Models\StudentSchedule;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Date;

/**
 * @extends Factory<ArcadePlay>
 */
final class ArcadePlayFactory extends Factory
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
            'game' => ArcadeGame::Horoscope,
            'choice' => 'aries',
            'played_on' => Date::now()->setTimezone((string) config('app.schedule_timezone'))->toDateString(),
            'result' => [],
        ];
    }
}
