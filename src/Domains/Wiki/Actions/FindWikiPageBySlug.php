<?php

declare(strict_types=1);

namespace NouTools\Domains\Wiki\Actions;

use App\Models\WikiPage;
use App\Models\WikiSlugRedirect;

/**
 * The live page behind a slug, following a rename redirect. Callers compare
 * the result's slug with the one asked for to send a 301.
 */
final readonly class FindWikiPageBySlug
{
    public function __invoke(string $slug): ?WikiPage
    {
        $page = WikiPage::query()->live()->where('slug', $slug)->first();

        if ($page !== null) {
            return $page;
        }

        $pageId = WikiSlugRedirect::query()->where('old_slug', $slug)->value('wiki_page_id');

        return $pageId === null ? null : WikiPage::query()->live()->find($pageId);
    }
}
