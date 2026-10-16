<?php

declare(strict_types=1);

namespace NouTools\Domains\Wiki\Markdown;

use League\CommonMark\Event\DocumentParsedEvent;

final readonly class WikiLinkResolutionProcessor
{
    public function __construct(private WikiPageResolver $resolver) {}

    public function __invoke(DocumentParsedEvent $event): void
    {
        $links = [];

        foreach ($event->getDocument()->iterator() as $node) {
            if ($node instanceof WikiLink) {
                $links[] = $node;
            }
        }

        if ($links === []) {
            return;
        }

        $targets = $this->resolver->resolve(array_map(fn (WikiLink $link): string => $link->slug, $links));

        foreach ($links as $link) {
            $link->target = $targets[$link->slug] ?? null;
        }
    }
}
