<?php

declare(strict_types=1);

namespace NouTools\Domains\Articles\Markdown;

use League\CommonMark\Environment\EnvironmentBuilderInterface;
use League\CommonMark\Event\DocumentParsedEvent;
use League\CommonMark\Extension\ExtensionInterface;
use NouTools\Domains\Articles\Markdown\Callout\CalloutContainerRenderer;
use NouTools\Domains\Articles\Markdown\Callout\CalloutHtmlBuilder;
use NouTools\Domains\Articles\Markdown\Callout\CalloutNode;
use NouTools\Domains\Articles\Markdown\Callout\CalloutNodeRenderer;
use NouTools\Domains\Articles\Markdown\Callout\CalloutStartParser;
use NouTools\Domains\Articles\Markdown\Container\ContainerNode;
use NouTools\Domains\Articles\Markdown\Container\ContainerNodeRenderer;
use NouTools\Domains\Articles\Markdown\Container\ContainerRendererRegistry;
use NouTools\Domains\Articles\Markdown\Container\ContainerStartParser;
use NouTools\Domains\Articles\Markdown\Container\Renderers\CardsRenderer;
use NouTools\Domains\Articles\Markdown\Container\Renderers\ChecklistRenderer;
use NouTools\Domains\Articles\Markdown\Container\Renderers\CtaRenderer;
use NouTools\Domains\Articles\Markdown\Container\Renderers\FaqRenderer;
use NouTools\Domains\Articles\Markdown\Container\Renderers\FigureRenderer;
use NouTools\Domains\Articles\Markdown\Container\Renderers\StepsRenderer;
use NouTools\Domains\Articles\Markdown\Container\Renderers\SummaryRenderer;
use NouTools\Domains\Articles\Markdown\Container\Renderers\TabsRenderer;
use NouTools\Domains\Articles\Markdown\Container\Renderers\TimelineRenderer;
use NouTools\Domains\Articles\Markdown\Dialogue\DialogueIndentedParagraphStartParser;
use NouTools\Domains\Articles\Markdown\Dialogue\DialogueRenderer;
use NouTools\Domains\Articles\Markdown\Dialogue\DialogueSpeakerStartParser;
use NouTools\Domains\Articles\Markdown\Dialogue\PersonaResolver;
use NouTools\Domains\Articles\Markdown\Heading\HeadingAnchorNode;
use NouTools\Domains\Articles\Markdown\Heading\HeadingAnchorRenderer;
use NouTools\Domains\Articles\Markdown\Heading\HeadingSlugProcessor;
use NouTools\Domains\Articles\Markdown\Inline\CjkAutolinkParser;
use NouTools\Domains\Articles\Markdown\Inline\Mark;
use NouTools\Domains\Articles\Markdown\Inline\MarkDelimiterProcessor;
use NouTools\Domains\Articles\Markdown\Inline\MarkRenderer;
use NouTools\Domains\Articles\Markdown\Toc\TocPlaceholderNode;
use NouTools\Domains\Articles\Markdown\Toc\TocPlaceholderRenderer;
use NouTools\Domains\Articles\Markdown\Toc\TocPlaceholderStartParser;

/**
 * Registers every NOU 小幫手 custom Markdown block/inline extension:
 * `:::` fenced containers (dialogue, callouts, steps, faq, tabs, cards,
 * timeline, summary, cta, figure, checklist), the GitHub-style `[!TYPE]`
 * alert callouts, `==mark==`, `[[toc]]`, and heading anchors.
 */
final readonly class NouMarkdownExtension implements ExtensionInterface
{
    public function register(EnvironmentBuilderInterface $environment): void
    {
        $calloutHtmlBuilder = new CalloutHtmlBuilder;

        $registry = new ContainerRendererRegistry;
        $registry->register('dialogue', new DialogueRenderer(new PersonaResolver));
        $registry->register('steps', new StepsRenderer);
        $registry->register('faq', new FaqRenderer);
        $registry->register('qa', new FaqRenderer);
        $registry->register('tabs', new TabsRenderer);
        $registry->register('cards', new CardsRenderer);
        $registry->register('timeline', new TimelineRenderer);
        $registry->register('summary', new SummaryRenderer);
        $registry->register('cta', new CtaRenderer);
        $registry->register('figure', new FigureRenderer);
        $registry->register('checklist', new ChecklistRenderer);

        foreach (\array_keys(config('markdown.callouts', [])) as $type) {
            $registry->register($type, new CalloutContainerRenderer($type, $calloutHtmlBuilder));
        }

        // Block-level containers: `:::name arg` ... `:::`
        $environment->addBlockStartParser(new ContainerStartParser, 30);
        $environment->addRenderer(ContainerNode::class, new ContainerNodeRenderer($registry));

        // `:::dialogue` turn splitting (only active inside a dialogue container).
        // Speaker names are CJK text, so this must outrank CommonMark's
        // SkipLinesStartingWithLettersParser (priority 200), which otherwise
        // aborts block-start scanning for any line beginning with a Unicode letter.
        $environment->addBlockStartParser(new DialogueSpeakerStartParser, 250);

        // An indented continuation paragraph inside a dialogue turn's body
        // stays prose instead of becoming an indented code block; must
        // outrank IndentedCodeStartParser (priority -100).
        $environment->addBlockStartParser(new DialogueIndentedParagraphStartParser, -90);

        // GitHub-style `> [!TIP]` alert callouts - must win over the plain
        // blockquote parser (priority 70), hence the higher priority here.
        $environment->addBlockStartParser(new CalloutStartParser($calloutHtmlBuilder), 80);
        $environment->addRenderer(CalloutNode::class, new CalloutNodeRenderer($calloutHtmlBuilder));

        // `[[toc]]` placeholder
        $environment->addBlockStartParser(new TocPlaceholderStartParser, 10);
        $environment->addRenderer(TocPlaceholderNode::class, new TocPlaceholderRenderer);

        // `==mark==`
        $environment->addDelimiterProcessor(new MarkDelimiterProcessor);
        $environment->addRenderer(Mark::class, new MarkRenderer);

        // Bare-URL autolinking after CJK punctuation/ideographs (GFM's own
        // autolinker only fires after whitespace/ASCII delimiters). Lower
        // priority than GFM's UrlAutolinkParser (priority 0) so it only ever
        // gets a turn when GFM's declines the match.
        $environment->addInlineParser(new CjkAutolinkParser, -10);

        // Heading anchors (h2-h4): `id` + trailing `#` permalink
        $environment->addEventListener(DocumentParsedEvent::class, new HeadingSlugProcessor, -100);
        $environment->addRenderer(HeadingAnchorNode::class, new HeadingAnchorRenderer);

        // External link handling
        $environment->addEventListener(DocumentParsedEvent::class, new ExternalLinkProcessor(
            (string) parse_url((string) config('app.url'), PHP_URL_HOST),
        ), -50);
    }
}
