<?php

declare(strict_types=1);

namespace NouTools\Domains\Wiki\Markdown;

use League\CommonMark\Node\Inline\AbstractInline;

/**
 * `[[slug]]`, `[[slug|label]]` or `[[slug#heading|label]]`. The target is
 * filled in by {@see WikiLinkResolutionProcessor} once the whole document is
 * parsed, so every link in a page costs one query, not one each.
 */
final class WikiLink extends AbstractInline
{
    /** Canonical slug and title of the live page, null when there isn't one (a red link). */
    public ?WikiLinkTarget $target = null;

    public function __construct(
        public readonly string $slug,
        public readonly ?string $anchor,
        public readonly ?string $label,
    ) {
        parent::__construct();
    }
}
