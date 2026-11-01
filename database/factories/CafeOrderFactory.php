<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\CafeOrder;
use App\Models\StudentSchedule;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Date;

/**
 * @extends Factory<CafeOrder>
 */
final class CafeOrderFactory extends Factory
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
            'name' => '美式咖啡',
            'emoji' => '☕',
            'cup_style' => 'mug',
            'to_go' => false,
            'price_paid' => 3,
            'expires_at' => Date::now()->addHours(2),
        ];
    }

    public function expired(): self
    {
        return $this->state(fn (): array => ['expires_at' => Date::now()->subMinute()]);
    }
}
