<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\CafeSeatKind;
use App\Models\CafeSeat;
use App\Models\StudentSchedule;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Date;

/**
 * @extends Factory<CafeSeat>
 */
final class CafeSeatFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $seatNumber = $this->faker->unique()->numberBetween(1, 99);

        return [
            'kind' => CafeSeatKind::Bar,
            'group_code' => null,
            'seat_number' => $seatNumber,
            'code' => sprintf('B%02d', $seatNumber),
            'label' => sprintf('吧台 %02d 號', $seatNumber),
            'student_schedule_id' => null,
            'occupied_at' => null,
            'last_seen_at' => null,
        ];
    }

    public function occupiedBy(StudentSchedule $schedule): self
    {
        return $this->state(fn (): array => [
            'student_schedule_id' => $schedule->id,
            'occupied_at' => Date::now(),
            'last_seen_at' => Date::now(),
        ]);
    }
}
