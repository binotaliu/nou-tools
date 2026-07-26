<?php

declare(strict_types=1);

namespace NouTools\Domains\Articles\Markdown\Heading;

use League\CommonMark\Event\DocumentParsedEvent;
use League\CommonMark\Extension\CommonMark\Node\Block\Heading;
use League\CommonMark\Node\NodeIterator;
use League\CommonMark\Node\StringContainerHelper;

final class HeadingSlugProcessor
{
    private const MIN_LEVEL = 2;

    private const MAX_LEVEL = 4;

    public function __invoke(DocumentParsedEvent $event): void
    {
        $slugger = new HeadingSlugger;

        foreach ($event->getDocument()->iterator(NodeIterator::FLAG_BLOCKS_ONLY) as $node) {
            if (! $node instanceof Heading) {
                continue;
            }

            if ($node->getLevel() < self::MIN_LEVEL || $node->getLevel() > self::MAX_LEVEL) {
                continue;
            }

            $text = StringContainerHelper::getChildText($node);
            $slug = $slugger->slugify($text);

            $node->data->set('attributes/id', $slug);
            $node->prependChild(new HeadingAnchorNode($slug));
        }
    }
}
