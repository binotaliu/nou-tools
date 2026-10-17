<?php

declare(strict_types=1);

namespace NouTools\Domains\Wiki\Support;

/**
 * The text of a fetched source. `url` is where redirects ended; `pages` is
 * the text per PDF page (an HTML page is a single entry).
 */
final readonly class FetchedWikiSource
{
    /**
     * @param  list<string>  $pages
     */
    public function __construct(
        public string $url,
        public string $text,
        public array $pages,
    ) {}
}
