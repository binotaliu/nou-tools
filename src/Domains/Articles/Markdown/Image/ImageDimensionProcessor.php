<?php

declare(strict_types=1);

namespace NouTools\Domains\Articles\Markdown\Image;

use League\CommonMark\Event\DocumentParsedEvent;
use League\CommonMark\Extension\CommonMark\Node\Inline\Image;

final class ImageDimensionProcessor
{
    public function __invoke(DocumentParsedEvent $event): void
    {
        foreach ($event->getDocument()->iterator() as $node) {
            if (! $node instanceof Image) {
                continue;
            }

            $dimensions = ImageDimensionResolver::resolve($node->getUrl());

            if ($dimensions === null) {
                continue;
            }

            $node->data->set('attributes/width', (string) $dimensions['width']);
            $node->data->set('attributes/height', (string) $dimensions['height']);
            $node->data->set('attributes/loading', 'lazy');
        }
    }
}
