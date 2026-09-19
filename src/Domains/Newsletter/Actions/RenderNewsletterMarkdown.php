<?php

declare(strict_types=1);

namespace NouTools\Domains\Newsletter\Actions;

use League\CommonMark\MarkdownConverter;
use NouTools\Domains\Articles\Markdown\ArticleMarkdownConverterFactory;

/**
 * Renders editor/AI-written newsletter Markdown through the same pipeline
 * as articles, so columns can use the article containers (hydrated by
 * useMarkdownContainers) and raw HTML in AI output is escaped.
 */
final class RenderNewsletterMarkdown
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
