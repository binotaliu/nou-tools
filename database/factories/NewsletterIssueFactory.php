<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\NewsletterIssueStatus;
use App\Models\NewsletterIssue;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Date;

/**
 * @extends Factory<NewsletterIssue>
 */
final class NewsletterIssueFactory extends Factory
{
    protected $model = NewsletterIssue::class;

    private static int $issueOffset = 0;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $publishesOn = Date::parse(config('newsletter.anchor_date'))
            ->addDays(14 * self::$issueOffset++);

        return $this->attributesForPublishDate($publishesOn);
    }

    public function publishingOn(string $date): static
    {
        return $this->state(fn (): array => $this->attributesForPublishDate(Date::parse($date)));
    }

    public function draft(): static
    {
        return $this->state(fn (): array => [
            'status' => NewsletterIssueStatus::Draft,
            'published_at' => null,
        ]);
    }

    public function ready(): static
    {
        return $this->state(fn (): array => [
            'status' => NewsletterIssueStatus::Ready,
            'published_at' => null,
        ]);
    }

    public function published(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => NewsletterIssueStatus::Published,
            'published_at' => $attributes['publishes_on'],
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function attributesForPublishDate(CarbonImmutable $publishesOn): array
    {
        return [
            'issue_key' => $publishesOn->format('o-\WW'),
            'publishes_on' => $publishesOn->toDateString(),
            'title' => null,
            'covers_from' => $publishesOn->subDays(14)->toDateString(),
            'covers_to' => $publishesOn->subDay()->toDateString(),
            'highlights_from' => $publishesOn->toDateString(),
            'highlights_to' => $publishesOn->addDays(13)->toDateString(),
            'highlights_intro' => fake()->paragraph(),
            'highlights_events' => [],
            'status' => NewsletterIssueStatus::Draft,
        ];
    }
}
