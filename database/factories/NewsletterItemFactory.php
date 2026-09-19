<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\NewsletterSection;
use App\Models\NewsletterIssue;
use App\Models\NewsletterItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<NewsletterItem>
 */
final class NewsletterItemFactory extends Factory
{
    protected $model = NewsletterItem::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'newsletter_issue_id' => NewsletterIssue::factory(),
            'announcement_id' => null,
            'section' => NewsletterSection::News,
            'source_name' => '教務處',
            'url' => fake()->url(),
            'headline' => fake()->sentence(),
            'summary' => fake()->paragraph(),
            'position' => 0,
        ];
    }

    public function arts(string $sourceName = '學務處'): static
    {
        return $this->state(fn (): array => [
            'section' => NewsletterSection::Arts,
            'source_name' => $sourceName,
        ]);
    }

    public function centers(string $sourceName = '臺北中心'): static
    {
        return $this->state(fn (): array => [
            'section' => NewsletterSection::Centers,
            'source_name' => $sourceName,
        ]);
    }
}
