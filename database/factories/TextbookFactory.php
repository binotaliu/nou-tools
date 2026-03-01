<?php

namespace Database\Factories;

use App\Models\Textbook;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Textbook>
 */
class TextbookFactory extends Factory
{
    protected $model = Textbook::class;

    public function definition(): array
    {
        return [
            'course_id' => null, // should be set when used
            'term' => '2025B',
            'book_title' => fake()->sentence(3),
            'edition' => fake()->optional()->randomNumber(1).'版',
            'price_info' => fake()->optional()->randomFloat(2, 100, 1000).'元',
            'reference_url' => fake()->optional()->url(),
        ];
    }
}
