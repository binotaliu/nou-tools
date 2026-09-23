<?php

declare(strict_types=1);

namespace NouTools\Domains\Changelog\Actions;

use App\Models\ChangelogPost;
use NouTools\Domains\Changelog\PageData\ChangelogIndexPageData;
use NouTools\Domains\Changelog\ViewModels\ChangelogPostSummaryViewModel;

final readonly class ShowChangelogIndexPage
{
    public function __invoke(): ChangelogIndexPageData
    {
        $latestPost = ChangelogPost::query()
            ->published()
            ->orderByDesc('published_at')
            ->first();

        $posts = ChangelogPost::query()
            ->published()
            ->when($latestPost !== null, fn ($query) => $query->whereKeyNot($latestPost->getKey()))
            ->orderByDesc('published_at')
            ->paginate(20)
            ->through(fn (ChangelogPost $post): ChangelogPostSummaryViewModel => ChangelogPostSummaryViewModel::fromModel($post));

        return new ChangelogIndexPageData(
            title: (string) config('changelog.title'),
            latestPost: $latestPost !== null ? ChangelogPostSummaryViewModel::fromModel($latestPost) : null,
            posts: $posts,
        );
    }
}
