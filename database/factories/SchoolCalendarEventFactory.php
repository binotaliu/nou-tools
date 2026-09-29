<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\SchoolCalendarEvent;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SchoolCalendarEvent>
 */
final class SchoolCalendarEventFactory extends Factory
{
    protected $model = SchoolCalendarEvent::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $start = fake()->dateTimeBetween('+1 day', '+60 days');

        return [
            'term' => config('app.current_semester'),
            'start_date' => $start->format('Y-m-d'),
            'end_date' => $start->format('Y-m-d'),
            'name' => fake()->unique()->sentence(3),
            'is_countdown' => false,
            'is_important' => true,
        ];
    }

    public function forTerm(string $term): static
    {
        return $this->state(['term' => $term]);
    }

    public function between(string $start, string $end): static
    {
        return $this->state(['start_date' => $start, 'end_date' => $end]);
    }

    public function countdown(): static
    {
        return $this->state(['is_countdown' => true]);
    }

    public function minor(): static
    {
        return $this->state(['is_important' => false]);
    }
}
