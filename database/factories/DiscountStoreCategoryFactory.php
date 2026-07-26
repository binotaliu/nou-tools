<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\DiscountStoreCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DiscountStoreCategory>
 */
final class DiscountStoreCategoryFactory extends Factory
{
    protected $model = DiscountStoreCategory::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->word(),
            'icon' => 'heroicon-o-tag',
            'sort_order' => fake()->numberBetween(0, 100),
        ];
    }
}
