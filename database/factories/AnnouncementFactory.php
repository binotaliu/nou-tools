<?php

namespace Database\Factories;

use App\Models\Announcement;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Announcement> */
class AnnouncementFactory extends Factory
{
    protected $model = Announcement::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'source_key' => fake()->slug(),
            'source_name' => fake()->company(),
            'category' => fake()->word(),
            'source_id' => (string) fake()->unique()->numberBetween(1000, 99999),
            'title' => fake()->sentence(),
            'url' => fake()->url(),
            'tags' => null,
            'published_at' => fake()->dateTimeBetween('-1 month', 'now'),
            'fetched_at' => now(),
            'expired_at' => null,
        ];
    }

    public function expired(): static
    {
        return $this->state(fn () => [
            'expired_at' => now(),
        ]);
    }

    /**
     * @param  string[]  $tags
     */
    public function withTags(array $tags): static
    {
        return $this->state(fn () => [
            'tags' => $tags,
        ]);
    }
}
