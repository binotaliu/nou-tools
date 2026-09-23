<?php

declare(strict_types=1);

namespace NouTools\Domains\Changelog\ViewModels;

use App\Models\ChangelogPost;
use NouTools\Domains\Changelog\Actions\RenderChangelogMarkdown;
use Spatie\LaravelData\Data;

final class ChangelogPostViewModel extends Data
{
    public function __construct(
        public string $slug,
        public string $title,
        // Plain HTML string rendered from Markdown; see ArticleIndexPageData::$indexContent.
        public string $bodyHtml,
        public bool $isPublished,
        public string $statusLabel,
        public ?string $publishedAt,
    ) {}

    public static function fromModel(ChangelogPost $post, RenderChangelogMarkdown $renderMarkdown): self
    {
        return new self(
            slug: $post->slug,
            title: $post->title,
            bodyHtml: $renderMarkdown($post->body),
            isPublished: $post->isPublished(),
            statusLabel: $post->status->label(),
            publishedAt: $post->published_at?->toIso8601String(),
        );
    }
}
