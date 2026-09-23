<?php

declare(strict_types=1);

namespace NouTools\Domains\Changelog\Actions;

use League\CommonMark\MarkdownConverter;
use NouTools\Domains\Articles\Markdown\ArticleMarkdownConverterFactory;

/**
 * Renders changelog post Markdown through the same pipeline as articles, so
 * posts can use the article containers (hydrated by useMarkdownContainers)
 * and raw HTML in the editor's input is escaped.
 */
final class RenderChangelogMarkdown
{
    private ?MarkdownConverter $converter = null;

    public function __construct(
        private readonly ArticleMarkdownConverterFactory $converterFactory,
    ) {}

    public function __invoke(?string $markdown): string
    {
        if (blank($markdown)) {
            return '';
        }

        $this->converter ??= $this->converterFactory->make();

        return $this->converter->convert((string) $markdown)->getContent();
    }
}
