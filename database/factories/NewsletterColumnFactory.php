<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\NewsletterColumn;
use App\Models\NewsletterIssue;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<NewsletterColumn>
 */
final class NewsletterColumnFactory extends Factory
{
    protected $model = NewsletterColumn::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'newsletter_issue_id' => NewsletterIssue::factory(),
            'title' => '浣熊站長的自言自語',
            'author' => '浣熊站長',
            'body' => fake()->paragraphs(2, true),
            'position' => 0,
        ];
    }
}
