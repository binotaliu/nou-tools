<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\DiscountStore;
use App\Models\DiscountStoreReport;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DiscountStoreReport>
 */
final class DiscountStoreReportFactory extends Factory
{
    protected $model = DiscountStoreReport::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'store_id' => DiscountStore::factory(),
            'is_valid' => fake()->boolean(),
            'comment' => fake()->optional()->sentence(),
        ];
    }

    public function valid(): static
    {
        return $this->state(fn (): array => ['is_valid' => true]);
    }

    public function invalid(): static
    {
        return $this->state(fn (): array => ['is_valid' => false]);
    }
}
