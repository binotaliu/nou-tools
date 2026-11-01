<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\CafeMenuItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CafeMenuItem>
 */
final class CafeMenuItemFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->unique()->word(),
            'emoji' => '☕',
            'cup_style' => 'mug',
            'description' => null,
            'price' => 3,
            'is_active' => true,
            'sort' => 0,
        ];
    }
}
