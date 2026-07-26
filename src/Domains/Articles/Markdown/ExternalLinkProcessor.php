<?php

declare(strict_types=1);

namespace NouTools\Domains\Articles\Markdown;

use League\CommonMark\Event\DocumentParsedEvent;
use League\CommonMark\Extension\CommonMark\Node\Inline\Link;

final class ExternalLinkProcessor
{
    public function __construct(private readonly string $internalHost) {}

    public function __invoke(DocumentParsedEvent $event): void
    {
        foreach ($event->getDocument()->iterator() as $node) {
            if (! $node instanceof Link) {
                continue;
            }

            if (! $this->isExternal($node->getUrl())) {
                continue;
            }

            $node->data->set('attributes/target', '_blank');
            $node->data->set('attributes/rel', 'noopener noreferrer');
            $node->data->append('attributes/class', 'md-link-external');
        }
    }

    private function isExternal(string $url): bool
    {
        if (! \preg_match('#^https?://#i', $url)) {
            return false;
        }

        $host = \parse_url($url, PHP_URL_HOST);

        if (! \is_string($host)) {
            return false;
        }

        return \strcasecmp($host, $this->internalHost) !== 0;
    }
}
