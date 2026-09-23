<?php

declare(strict_types=1);

namespace NouTools\Domains\Articles\Markdown\Heading;

use League\CommonMark\Event\DocumentParsedEvent;
use League\CommonMark\Extension\CommonMark\Node\Block\Heading;
use League\CommonMark\Node\NodeIterator;

/**
 * The page chrome (article/KB/manual/newsletter templates) always supplies
 * its own single `<h1>` for the document title, so a Markdown body that
 * starts with `# ...` would otherwise duplicate it — a screen reader then
 * announces the same heading twice in a row. Any `# ` heading written
 * inside the body is demoted to `##` so the body can never emit an `<h1>`.
 *
 * Must run before HeadingSlugProcessor so the demoted level is what gets
 * slugged and anchored.
 */
final class HeadingLevelClampProcessor
{
    private const int BODY_MIN_LEVEL = 2;

    public function __invoke(DocumentParsedEvent $event): void
    {
        foreach ($event->getDocument()->iterator(NodeIterator::FLAG_BLOCKS_ONLY) as $node) {
            if (! $node instanceof Heading) {
                continue;
            }

            if ($node->getLevel() < self::BODY_MIN_LEVEL) {
                $node->setLevel(self::BODY_MIN_LEVEL);
            }
        }
    }
}
