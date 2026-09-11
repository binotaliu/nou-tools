<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\StudySeatKind;
use App\Models\StudentSchedule;
use App\Models\StudyRoomSeat;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StudyRoomSeat>
 */
final class StudyRoomSeatFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $seatNumber = $this->faker->unique()->numberBetween(1, 12);

        return [
            'floor' => 1,
            'kind' => StudySeatKind::Solo,
            'group_code' => null,
            'seat_number' => $seatNumber,
            'code' => 'F1-S'.$seatNumber.'-'.$this->faker->unique()->bothify('####'),
            'label' => '1F 單人座 '.$seatNumber,
            'student_schedule_id' => null,
            'occupied_at' => null,
            'last_seen_at' => null,
        ];
    }

    /**
     * Mark the seat as occupied by the given schedule.
     */
    public function occupiedBy(StudentSchedule $schedule): self
    {
        return $this->state(fn (array $attributes): array => [
            'student_schedule_id' => $schedule->id,
            'occupied_at' => now(),
            'last_seen_at' => now(),
        ]);
    }

    /**
     * A shared-table seat belonging to the given table group code (e.g. "T1").
     */
    public function shared(string $groupCode, int $seatNumber): self
    {
        return $this->state(fn (array $attributes): array => [
            'kind' => StudySeatKind::Shared,
            'group_code' => $groupCode,
            'seat_number' => $seatNumber,
            'label' => '共享桌 '.$groupCode.' 座位 '.$seatNumber,
        ]);
    }
}
