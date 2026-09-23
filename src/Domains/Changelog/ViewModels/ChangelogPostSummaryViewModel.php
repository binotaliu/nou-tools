<?php

declare(strict_types=1);

namespace NouTools\Domains\Changelog\ViewModels;

use App\Models\ChangelogPost;
use Spatie\LaravelData\Data;

final class ChangelogPostSummaryViewModel extends Data
{
    public function __construct(
        public string $slug,
        public string $title,
        public ?string $publishedAt,
        public string $url,
    ) {}

    public static function fromModel(ChangelogPost $post): self
    {
        return new self(
            slug: $post->slug,
            title: $post->title,
            publishedAt: $post->published_at?->toIso8601String(),
            url: route('changelog.show', $post->slug),
        );
    }
}
