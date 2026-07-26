<?php

declare(strict_types=1);

namespace NouTools\Domains\Articles\Actions;

use App\Enums\ArticleType;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\File;
use Illuminate\Support\HtmlString;
use League\CommonMark\Extension\FrontMatter\Output\RenderedContentWithFrontMatter;
use NouTools\Domains\Articles\Markdown\ArticleMarkdownConverterFactory;
use NouTools\Domains\Articles\PageData\ArticleShowPageData;
use NouTools\Domains\Articles\ViewModels\ArticleViewModel;

final readonly class ShowArticlePage
{
    public function __construct(private ArticleMarkdownConverterFactory $converterFactory) {}

    public function __invoke(ArticleType $type, string $slug): ?ArticleShowPageData
    {
        if (! $this->isValidSlug($slug)) {
            return null;
        }

        $articlePath = resource_path("articles/{$type->directory()}/{$slug}.md");

        if (! File::exists($articlePath)) {
            return null;
        }

        $converter = $this->converterFactory->make();
        $result = $converter->convert(File::get($articlePath));

        if (! $result instanceof RenderedContentWithFrontMatter) {
            return null;
        }

        $frontMatter = $result->getFrontMatter();
        $sidebarPath = resource_path("articles/{$type->directory()}/_sidebar.md");

        return new ArticleShowPageData(
            article: new ArticleViewModel(
                slug: $slug,
                type: $type,
                title: $frontMatter['title'] ?? 'Untitled',
                author: $frontMatter['author'] ?? 'Unknown',
                publishedAt: isset($frontMatter['published_at'])
                    ? Date::parse($frontMatter['published_at'])
                    : Date::now(),
                updatedAt: isset($frontMatter['updated_at'])
                    ? Date::parse($frontMatter['updated_at'])
                    : null,
                content: $result->getContent(),
                description: $frontMatter['description'] ?? '',
            ),
            sidebarContent: File::exists($sidebarPath)
                ? new HtmlString($converter->convert(File::get($sidebarPath))->getContent())
                : null,
        );
    }

    private function isValidSlug(string $slug): bool
    {
        return (bool) preg_match('/^[a-z0-9_-]+$/', $slug);
    }
}
