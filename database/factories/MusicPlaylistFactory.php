<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\MusicPlaylist;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MusicPlaylist>
 */
final class MusicPlaylistFactory extends Factory
{
    protected $model = MusicPlaylist::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'title' => fake()->words(2, true),
            'description' => fake()->sentence(),
            'cover_image' => null,
        ];
    }
}
