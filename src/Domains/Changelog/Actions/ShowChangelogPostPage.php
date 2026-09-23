<?php

declare(strict_types=1);

namespace NouTools\Domains\Changelog\Actions;

use App\Models\ChangelogPost;
use NouTools\Domains\Changelog\PageData\ChangelogPostPageData;
use NouTools\Domains\Changelog\ViewModels\ChangelogPostSummaryViewModel;
use NouTools\Domains\Changelog\ViewModels\ChangelogPostViewModel;

final readonly class ShowChangelogPostPage
{
    public function __construct(
        private RenderChangelogMarkdown $renderChangelogMarkdown,
    ) {}

    public function __invoke(string $slug, bool $includeUnpublished = false): ?ChangelogPostPageData
    {
        $post = ChangelogPost::query()
            ->where('slug', $slug)
            ->when(! $includeUnpublished, fn ($query) => $query->published())
            ->first();

        if ($post === null) {
            return null;
        }

        $previousPost = ChangelogPost::query()
            ->published()
            ->where('published_at', '<', $post->published_at)
            ->orderByDesc('published_at')
            ->first();

        $nextPost = ChangelogPost::query()
            ->published()
            ->where('published_at', '>', $post->published_at)
            ->orderBy('published_at')
            ->first();

        return new ChangelogPostPageData(
            title: (string) config('changelog.title'),
            post: ChangelogPostViewModel::fromModel($post, $this->renderChangelogMarkdown),
            previousPost: $previousPost !== null ? ChangelogPostSummaryViewModel::fromModel($previousPost) : null,
            nextPost: $nextPost !== null ? ChangelogPostSummaryViewModel::fromModel($nextPost) : null,
        );
    }
}
