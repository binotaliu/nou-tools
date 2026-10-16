<?php

declare(strict_types=1);

namespace NouTools\Domains\Wiki\Markdown;

use League\CommonMark\Node\Inline\AbstractInline;

/**
 * `[[slug]]`, `[[slug|label]]` or `[[slug#heading|label]]` as parsed. It only
 * lives until {@see WikiLinkResolutionProcessor} has looked up every target in
 * the document at once and swapped it for a plain link.
 */
final class WikiLink extends AbstractInline
{
    public function __construct(
        public readonly string $slug,
        public readonly ?string $anchor,
        public readonly ?string $label,
    ) {
        parent::__construct();
    }
}
