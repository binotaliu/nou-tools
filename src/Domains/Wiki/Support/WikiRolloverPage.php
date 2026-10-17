<?php

declare(strict_types=1);

namespace NouTools\Domains\Wiki\Support;

use App\Models\WikiPage;

/**
 * A wiki page that cites the previous document in a rollover, with how each
 * section it may rely on compares with the new document. `status` is
 * `changed` (same title, different text), `unmatched` (no section with that
 * title in the new document) or `unchanged`. `pageKnown` is false when a
 * citation had no usable `#page=`, so every section is a candidate.
 */
final readonly class WikiRolloverPage
{
    /**
     * @param  list<array{key: string, title: string, status: string, newKey: string|null}>  $sections
     */
    public function __construct(
        public WikiPage $page,
        public array $sections,
        public bool $pageKnown,
    ) {}

    /**
     * @return list<array{key: string, title: string, status: string, newKey: string|null}>
     */
    public function changedSections(): array
    {
        return array_values(array_filter($this->sections, fn (array $section): bool => $section['status'] !== 'unchanged'));
    }

    public function isAffected(): bool
    {
        return $this->changedSections() !== [];
    }
}
