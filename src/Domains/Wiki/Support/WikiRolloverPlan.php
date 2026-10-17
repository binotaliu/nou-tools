<?php

declare(strict_types=1);

namespace NouTools\Domains\Wiki\Support;

use App\Models\WikiSourceDocument;

/**
 * What a semester rollover would touch: the pages citing the previous
 * document, split into those that rely on a changed section and those that
 * don't.
 */
final readonly class WikiRolloverPlan
{
    /**
     * @param  list<WikiRolloverPage>  $pages
     */
    public function __construct(
        public WikiSourceDocument $previous,
        public WikiSourceDocument $current,
        public array $pages,
    ) {}

    /**
     * @return list<WikiRolloverPage>
     */
    public function affected(): array
    {
        return array_values(array_filter($this->pages, fn (WikiRolloverPage $page): bool => $page->isAffected()));
    }

    /**
     * @return list<WikiRolloverPage>
     */
    public function unaffected(): array
    {
        return array_values(array_filter($this->pages, fn (WikiRolloverPage $page): bool => ! $page->isAffected()));
    }
}
