<?php

declare(strict_types=1);

namespace NouTools\Domains\Wiki\Markdown;

use League\CommonMark\MarkdownConverter;
use NouTools\Domains\Articles\Markdown\ArticleMarkdownConverterFactory;

/**
 * The article pipeline (containers, callouts, heading anchors, YouTube
 * embeds, raw HTML stripped) plus the wiki dialect, without front matter.
 */
final readonly class WikiMarkdownConverterFactory
{
    public function __construct(
        private ArticleMarkdownConverterFactory $articleConverterFactory,
        private WikiPageResolver $resolver,
    ) {}

    public function make(): MarkdownConverter
    {
        $environment = $this->articleConverterFactory->baseEnvironment([
            'footnote' => [
                'enable_inline_footnotes' => false,
                'container_add_hr' => false,
            ],
        ]);
        $environment->addExtension(new WikiMarkdownExtension($this->resolver));

        return new MarkdownConverter($environment);
    }
}
