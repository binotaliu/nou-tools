<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\MusicTrack;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MusicTrack>
 */
final class MusicTrackFactory extends Factory
{
    protected $model = MusicTrack::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $slug = fake()->unique()->slug(2);

        return [
            'title' => fake()->words(3, true),
            'author' => fake()->name(),
            'license' => 'CC BY 4.0',
            'license_url' => 'https://creativecommons.org/licenses/by/4.0/',
            'source_url' => fake()->url(),
            'mp3_path' => "{$slug}.mp3",
            'ogg_path' => "{$slug}.ogg",
            'duration_seconds' => fake()->numberBetween(90, 420),
        ];
    }
}
