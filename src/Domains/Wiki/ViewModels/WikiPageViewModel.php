<?php

declare(strict_types=1);

namespace NouTools\Domains\Wiki\ViewModels;

use App\Models\WikiPage;
use App\Models\WikiRevision;
use NouTools\Domains\Wiki\Actions\RenderWikiMarkdown;
use Spatie\LaravelData\Data;

final class WikiPageViewModel extends Data
{
    /**
     * @param  list<WikiCategoryLinkViewModel>  $categories
     */
    public function __construct(
        public string $slug,
        public string $title,
        public ?string $description,
        // Plain HTML string: spatie/laravel-data serialises HtmlString to {}.
        public string $content,
        public array $categories,
        public string $updatedAt,
        public bool $isProtected,
        public string $url,
        public string $editUrl,
        public string $historyUrl,
    ) {}

    public static function fromModel(WikiPage $page, WikiRevision $revision, RenderWikiMarkdown $render): self
    {
        return new self(
            slug: $page->slug,
            title: $revision->title,
            description: $revision->description,
            content: $render($revision->body),
            categories: array_map(fn (string $name): WikiCategoryLinkViewModel => WikiCategoryLinkViewModel::fromName($name), $revision->categories),
            updatedAt: $revision->created_at->toIso8601String(),
            isProtected: $page->is_protected,
            url: route('wiki.show', $page->slug),
            editUrl: route('wiki.edit', $page->slug),
            historyUrl: route('wiki.history', $page->slug),
        );
    }
}
