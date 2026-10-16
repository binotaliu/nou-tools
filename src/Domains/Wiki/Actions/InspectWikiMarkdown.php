<?php

declare(strict_types=1);

namespace NouTools\Domains\Wiki\Actions;

use League\CommonMark\Extension\CommonMark\Node\Inline\Link;
use League\CommonMark\Parser\MarkdownParser;
use NouTools\Domains\Wiki\Markdown\WikiLink;
use NouTools\Domains\Wiki\Markdown\WikiMarkdownConverterFactory;

/**
 * Parses wiki Markdown (without rendering) and reports what it links to: the
 * `[[slug]]` targets for the link index, and the external URLs, which the
 * publish rules compare with the allowed source domains.
 */
final class InspectWikiMarkdown
{
    private ?MarkdownParser $parser = null;

    public function __construct(
        private readonly WikiMarkdownConverterFactory $converterFactory,
    ) {}

    /**
     * @return array{wikiLinks: list<string>, externalUrls: list<string>}
     */
    public function __invoke(string $markdown): array
    {
        $this->parser ??= new MarkdownParser($this->converterFactory->make()->getEnvironment());

        $wikiLinks = [];
        $externalUrls = [];

        foreach ($this->parser->parse($markdown)->iterator() as $node) {
            if ($node instanceof WikiLink) {
                $wikiLinks[] = $node->slug;
            } elseif ($node instanceof Link && preg_match('#^https?://#i', $node->getUrl()) === 1) {
                $externalUrls[] = $node->getUrl();
            }
        }

        return [
            'wikiLinks' => array_values(array_unique($wikiLinks)),
            'externalUrls' => array_values(array_unique($externalUrls)),
        ];
    }
}
