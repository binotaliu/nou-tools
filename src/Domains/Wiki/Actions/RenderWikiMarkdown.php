<?php

declare(strict_types=1);

namespace NouTools\Domains\Wiki\Actions;

use League\CommonMark\MarkdownConverter;
use NouTools\Domains\Wiki\Markdown\WikiMarkdownConverterFactory;

/**
 * Renders wiki Markdown (page bodies and drafts) to HTML that is
 * mounted with `v-html` and hydrated by useMarkdownContainers.
 */
final class RenderWikiMarkdown
{
    private ?MarkdownConverter $converter = null;

    public function __construct(
        private readonly WikiMarkdownConverterFactory $converterFactory,
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
