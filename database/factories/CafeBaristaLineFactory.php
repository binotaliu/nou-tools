<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\BaristaTrigger;
use App\Models\CafeBaristaLine;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CafeBaristaLine>
 */
final class CafeBaristaLineFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'trigger' => BaristaTrigger::SmallTalk,
            'conditions' => null,
            'text' => $this->faker->sentence(),
            'mood' => 'happy',
            'replies' => null,
            'priority' => 0,
            'weight' => 1,
            'is_active' => true,
        ];
    }
}
