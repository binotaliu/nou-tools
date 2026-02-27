<?php

namespace App\Services;

use App\Data\Article;
use App\Enums\ArticleType;
use Carbon\Carbon;
use Illuminate\Support\Facades\File;
use Illuminate\Support\HtmlString;
use League\CommonMark\Extension\CommonMark\CommonMarkCoreExtension;
use League\CommonMark\Extension\FrontMatter\FrontMatterExtension;
use League\CommonMark\Extension\FrontMatter\Output\RenderedContentWithFrontMatter;
use League\CommonMark\MarkdownConverter;

class ArticleService
{
    private MarkdownConverter $converter;

    public function __construct()
    {
        $environment = new \League\CommonMark\Environment\Environment;
        $environment->addExtension(new CommonMarkCoreExtension);
        $environment->addExtension(new FrontMatterExtension);

        $this->converter = new MarkdownConverter($environment);
    }

    public function getArticle(ArticleType $type, string $slug): ?Article
    {
        // reject suspicious slugs early so we never touch the filesystem with
        // a path that could be influenced by an attacker. only allow
        // lowercase letters, numbers, hyphens and underscores.
        if (! $this->isValidSlug($slug)) {
            return null;
        }

        $path = $this->buildArticlePath($type, $slug);

        if (! File::exists($path)) {
            return null;
        }

        $markdown = File::get($path);
        $result = $this->converter->convert($markdown);

        if (! $result instanceof RenderedContentWithFrontMatter) {
            return null;
        }

        $frontMatter = $result->getFrontMatter();

        return new Article(
            slug: $slug,
            type: $type,
            title: $frontMatter['title'] ?? 'Untitled',
            author: $frontMatter['author'] ?? 'Unknown',
            publishedAt: isset($frontMatter['published_at'])
                ? Carbon::parse($frontMatter['published_at'])
                : Carbon::now(),
            content: $result->getContent(),
            description: $frontMatter['description'] ?? '',
        );
    }

    public function getIndex(ArticleType $type): ?HtmlString
    {
        $path = resource_path("articles/{$type->directory()}/_index.md");

        if (! File::exists($path)) {
            return null;
        }

        $markdown = File::get($path);
        $result = $this->converter->convert($markdown);

        return new HtmlString($result->getContent());
    }

    public function getSidebar(ArticleType $type): ?HtmlString
    {
        $path = resource_path("articles/{$type->directory()}/_sidebar.md");

        if (! File::exists($path)) {
            return null;
        }

        $markdown = File::get($path);
        $result = $this->converter->convert($markdown);

        return new HtmlString($result->getContent());
    }

    /**
     * Determine if the provided slug is safe to use as a file name.
     *
     * We only permit alphanumeric characters, hyphens, and underscores so
     * attackers can't supply values like "../.env" or "\0" to escape the
     * articles directory.  Returning false will cause the public-facing
     * methods to behave as though the file simply doesn't exist.
     */
    private function isValidSlug(string $slug): bool
    {
        return (bool) preg_match('/^[a-z0-9_-]+$/', $slug);
    }

    /**
     * Build the absolute path to an article, ensuring it cannot escape the
     * expected articles directory.  We rely on slug validation above, but the
     * additional sanity check makes reasoning easier and guards against future
     * changes.
     */
    private function buildArticlePath(ArticleType $type, string $slug): string
    {
        $base = resource_path("articles/{$type->directory()}");

        // NB: we intentionally avoid realpath() here because it returns false
        // for non‑existent files. the slug regex already blocks traversal, so a
        // simple concatenation is sufficient.
        return $base.DIRECTORY_SEPARATOR.$slug.'.md';
    }
}
