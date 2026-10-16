<?php

declare(strict_types=1);

namespace NouTools\Domains\Wiki\Markdown;

use League\CommonMark\Event\DocumentParsedEvent;
use League\CommonMark\Extension\CommonMark\Node\Inline\Link;
use NouTools\Domains\Articles\Markdown\Heading\HeadingSlugger;

/**
 * Resolves every `[[slug]]` in the document with one lookup and replaces it
 * with an ordinary {@see Link}, so containers that look for links (`:::cards`)
 * and the rest of the pipeline treat it like any other link. A link to a live
 * page shows its current title unless a label is given; a link to a page that
 * doesn't exist yet becomes a red link to the new-page form.
 */
final readonly class WikiLinkResolutionProcessor
{
    /** Data key carrying the slug as written, read by InspectWikiMarkdown. */
    public const string SLUG_DATA_KEY = 'wiki_slug';

    public function __construct(private WikiPageResolver $resolver) {}

    public function __invoke(DocumentParsedEvent $event): void
    {
        $wikiLinks = [];

        foreach ($event->getDocument()->iterator() as $node) {
            if ($node instanceof WikiLink) {
                $wikiLinks[] = $node;
            }
        }

        if ($wikiLinks === []) {
            return;
        }

        $targets = $this->resolver->resolve(array_map(fn (WikiLink $link): string => $link->slug, $wikiLinks));

        foreach ($wikiLinks as $wikiLink) {
            $wikiLink->replaceWith($this->toLink($wikiLink, $targets[$wikiLink->slug] ?? null));
        }
    }

    private function toLink(WikiLink $wikiLink, ?WikiLinkTarget $target): Link
    {
        if ($target === null) {
            $link = new Link('/wiki/new?slug='.rawurlencode($wikiLink->slug), $wikiLink->label ?? $wikiLink->slug, '尚未建立的條目');
            $link->data->set('attributes/class', 'wiki-link wiki-link-missing');
        } else {
            $anchor = $wikiLink->anchor !== null
                ? '#'.rawurlencode((new HeadingSlugger)->slugify($wikiLink->anchor))
                : '';

            $link = new Link('/wiki/'.$target->slug.$anchor, $wikiLink->label ?? $target->title);
            $link->data->set('attributes/class', 'wiki-link');
        }

        $link->data->set(self::SLUG_DATA_KEY, $wikiLink->slug);

        return $link;
    }
}
