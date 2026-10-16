<?php

declare(strict_types=1);

namespace NouTools\Domains\Articles\Markdown;

use League\CommonMark\Environment\Environment;
use League\CommonMark\Extension\CommonMark\CommonMarkCoreExtension;
use League\CommonMark\Extension\FrontMatter\FrontMatterExtension;
use League\CommonMark\Extension\GithubFlavoredMarkdownExtension;
use League\CommonMark\MarkdownConverter;

/**
 * Builds the single, shared CommonMark pipeline used to render article
 * Markdown (both full articles and their `_index.md` / `_sidebar.md`
 * companions): CommonMark core + GFM (tables, strikethrough, autolinks,
 * task lists) + front matter + our custom NOU 小幫手 block/inline syntax.
 */
final readonly class ArticleMarkdownConverterFactory
{
    public function make(): MarkdownConverter
    {
        $environment = $this->baseEnvironment();
        $environment->addExtension(new FrontMatterExtension);

        return new MarkdownConverter($environment);
    }

    /**
     * The pipeline without front matter, for callers that build their own
     * dialect on top of it (the wiki adds `[[links]]` and footnotes, and its
     * bodies must not lose a leading `---` to the front matter parser).
     *
     * @param  array<string, mixed>  $extraConfig
     */
    public function baseEnvironment(array $extraConfig = []): Environment
    {
        $environment = new Environment([...$this->config(), ...$extraConfig]);
        $environment->addExtension(new CommonMarkCoreExtension);
        $environment->addExtension(new GithubFlavoredMarkdownExtension);
        $environment->addExtension(new NouMarkdownExtension);

        return $environment;
    }

    /**
     * @return array<string, mixed>
     */
    private function config(): array
    {
        return [
            'html_input' => config('markdown.commonmark.html_input', 'escape'),
            'allow_unsafe_links' => config('markdown.commonmark.allow_unsafe_links', false),
        ];
    }
}
