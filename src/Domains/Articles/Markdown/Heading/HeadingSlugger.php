<?php

declare(strict_types=1);

namespace NouTools\Domains\Articles\Markdown\Heading;

/**
 * Generates Chinese-safe, deduplicated heading slugs: CJK characters are kept
 * as-is, punctuation is stripped, whitespace becomes a dash, and repeated
 * slugs within a document get a numeric `-2`, `-3`, ... suffix.
 */
final class HeadingSlugger
{
    /** @var array<string, int> */
    private array $seen = [];

    public function slugify(string $text): string
    {
        $slug = \trim($text);
        $slug = \mb_strtolower($slug, 'UTF-8');
        $slug = \preg_replace('/\s+/u', '-', $slug) ?? $slug;
        $slug = \preg_replace('/[^\p{L}\p{N}\p{M}-]+/u', '', $slug) ?? $slug;
        $slug = \trim($slug, '-');

        if ($slug === '') {
            $slug = 'section';
        }

        if (! isset($this->seen[$slug])) {
            $this->seen[$slug] = 1;

            return $slug;
        }

        $this->seen[$slug]++;

        return $slug.'-'.$this->seen[$slug];
    }
}
