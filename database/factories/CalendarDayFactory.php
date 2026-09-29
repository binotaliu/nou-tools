<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\CalendarDay;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CalendarDay>
 */
final class CalendarDayFactory extends Factory
{
    protected $model = CalendarDay::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'date' => fake()->unique()->dateTimeBetween('+1 day', '+365 days')->format('Y-m-d'),
            'is_red' => true,
            'label' => null,
        ];
    }

    public function on(string $date): static
    {
        return $this->state(['date' => $date]);
    }

    public function plain(): static
    {
        return $this->state(['is_red' => false]);
    }

    public function labelled(string $label): static
    {
        return $this->state(['label' => $label]);
    }
}
