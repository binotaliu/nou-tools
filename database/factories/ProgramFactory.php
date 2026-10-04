<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Program;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Program>
 */
final class ProgramFactory extends Factory
{
    protected $model = Program::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'term' => '2026A',
            'region' => 'tc',
            'name' => '測試專班'.fake()->unique()->numerify('###'),
            'position' => 0,
        ];
    }
}
