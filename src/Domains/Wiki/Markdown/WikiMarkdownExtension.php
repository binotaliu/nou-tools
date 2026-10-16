<?php

declare(strict_types=1);

namespace NouTools\Domains\Wiki\Markdown;

use League\CommonMark\Environment\EnvironmentBuilderInterface;
use League\CommonMark\Event\DocumentParsedEvent;
use League\CommonMark\Extension\ExtensionInterface;
use League\CommonMark\Extension\Footnote\FootnoteExtension;
use League\CommonMark\Extension\Footnote\Node\FootnoteContainer;

/**
 * The wiki's additions to the article dialect: `[[slug]]` links and
 * footnote citations rendered as 參考資料.
 */
final readonly class WikiMarkdownExtension implements ExtensionInterface
{
    public function __construct(private WikiPageResolver $resolver) {}

    public function register(EnvironmentBuilderInterface $environment): void
    {
        $environment->addExtension(new FootnoteExtension);

        // Above CommonMark's bracket parsers, so `[[` never starts a link.
        $environment->addInlineParser(new WikiLinkParser, 100);
        // Before ExternalLinkProcessor (-50) and anything else that inspects links.
        $environment->addEventListener(DocumentParsedEvent::class, new WikiLinkResolutionProcessor($this->resolver), 50);

        $environment->addRenderer(FootnoteContainer::class, new WikiReferencesRenderer, 10);
    }
}
