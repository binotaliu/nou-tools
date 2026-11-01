<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\StickyNoteStatus;
use App\Models\CafeStickyNote;
use App\Models\StudentSchedule;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Date;

/**
 * @extends Factory<CafeStickyNote>
 */
final class CafeStickyNoteFactory extends Factory
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
            'text' => $this->faker->realText(20),
            'drawing_path' => null,
            'paper_color' => 'yellow',
            'x' => $this->faker->randomFloat(3, 0.1, 0.9),
            'y' => $this->faker->randomFloat(3, 0.1, 0.9),
            'rotation' => $this->faker->numberBetween(-6, 6),
            'status' => StickyNoteStatus::Published,
            'report_count' => 0,
            'published_at' => Date::now(),
            'expires_at' => Date::now()->addDays(14),
        ];
    }

    public function status(StickyNoteStatus $status): self
    {
        return $this->state(fn (): array => [
            'status' => $status,
            'published_at' => $status === StickyNoteStatus::Published ? Date::now() : null,
            'expires_at' => $status === StickyNoteStatus::Published ? Date::now()->addDays(14) : null,
        ]);
    }

    public function expired(): self
    {
        return $this->state(fn (): array => [
            'published_at' => Date::now()->subDays(20),
            'expires_at' => Date::now()->subDays(6),
        ]);
    }
}
