<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\ChangelogPostStatus;
use App\Models\ChangelogPost;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<ChangelogPost>
 */
final class ChangelogPostFactory extends Factory
{
    protected $model = ChangelogPost::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $title = fake()->unique()->sentence();

        return [
            'slug' => Str::slug($title).'-'.fake()->unique()->numberBetween(1, 100000),
            'title' => $title,
            'body' => fake()->paragraphs(3, true),
            'status' => ChangelogPostStatus::Draft,
            'published_at' => null,
        ];
    }

    public function published(): static
    {
        return $this->state(fn (): array => [
            'status' => ChangelogPostStatus::Published,
            'published_at' => fake()->dateTimeBetween('-1 month'),
        ]);
    }
}
