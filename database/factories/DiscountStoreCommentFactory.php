<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\DiscountStore;
use App\Models\DiscountStoreComment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DiscountStoreComment>
 */
final class DiscountStoreCommentFactory extends Factory
{
    protected $model = DiscountStoreComment::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'store_id' => DiscountStore::factory(),
            'nickname' => fake()->name(),
            'content' => fake()->paragraph(),
            'is_approved' => false,
        ];
    }

    public function approved(): static
    {
        return $this->state(fn (): array => ['is_approved' => true]);
    }
}
