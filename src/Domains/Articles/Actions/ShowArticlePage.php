<?php

namespace NouTools\Domains\Articles\Actions;

use App\Enums\ArticleType;
use Carbon\Carbon;
use Illuminate\Support\Facades\File;
use Illuminate\Support\HtmlString;
use League\CommonMark\Environment\Environment;
use League\CommonMark\Extension\CommonMark\CommonMarkCoreExtension;
use League\CommonMark\Extension\FrontMatter\FrontMatterExtension;
use League\CommonMark\Extension\FrontMatter\Output\RenderedContentWithFrontMatter;
use League\CommonMark\MarkdownConverter;
use NouTools\Domains\Articles\ViewModels\ArticleShowPageViewModel;
use NouTools\Domains\Articles\ViewModels\ArticleViewModel;

final class ShowArticlePage
{
    public function __invoke(ArticleType $type, string $slug): ?ArticleShowPageViewModel
    {
        if (! $this->isValidSlug($slug)) {
            return null;
        }

        $articlePath = resource_path("articles/{$type->directory()}/{$slug}.md");

        if (! File::exists($articlePath)) {
            return null;
        }

        $converter = $this->buildConverter();
        $result = $converter->convert(File::get($articlePath));

        if (! $result instanceof RenderedContentWithFrontMatter) {
            return null;
        }

        $frontMatter = $result->getFrontMatter();
        $sidebarPath = resource_path("articles/{$type->directory()}/_sidebar.md");

        return new ArticleShowPageViewModel(
            article: new ArticleViewModel(
                slug: $slug,
                type: $type,
                title: $frontMatter['title'] ?? 'Untitled',
                author: $frontMatter['author'] ?? 'Unknown',
                publishedAt: isset($frontMatter['published_at'])
                    ? Carbon::parse($frontMatter['published_at'])
                    : Carbon::now(),
                content: $result->getContent(),
                description: $frontMatter['description'] ?? '',
            ),
            sidebarContent: File::exists($sidebarPath)
                ? new HtmlString($converter->convert(File::get($sidebarPath))->getContent())
                : null,
        );
    }

    private function buildConverter(): MarkdownConverter
    {
        $environment = new Environment;
        $environment->addExtension(new CommonMarkCoreExtension);
        $environment->addExtension(new FrontMatterExtension);

        return new MarkdownConverter($environment);
    }

    private function isValidSlug(string $slug): bool
    {
        return (bool) preg_match('/^[a-z0-9_-]+$/', $slug);
    }
}
