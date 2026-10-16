<?php

declare(strict_types=1);

namespace NouTools\Domains\Wiki\Markdown;

use App\Models\WikiPage;
use App\Models\WikiSlugRedirect;

/**
 * Looks up the live pages behind a set of slugs, following rename redirects,
 * so `[[old-slug]]` still points at the page under its current slug.
 */
class WikiPageResolver
{
    /**
     * @param  list<string>  $slugs
     * @return array<string, WikiLinkTarget>
     */
    public function resolve(array $slugs): array
    {
        $slugs = array_values(array_unique($slugs));

        if ($slugs === []) {
            return [];
        }

        $redirects = WikiSlugRedirect::query()
            ->whereIn('old_slug', $slugs)
            ->pluck('wiki_page_id', 'old_slug');

        $pages = WikiPage::query()
            ->live()
            ->where(fn ($query) => $query
                ->whereIn('slug', $slugs)
                ->orWhereIn('id', $redirects->values()))
            ->with('currentRevision:id,title')
            ->get(['id', 'slug', 'current_revision_id']);

        $targets = [];

        foreach ($pages as $page) {
            $target = new WikiLinkTarget($page->slug, (string) $page->currentRevision?->title);

            $targets[$page->slug] = $target;

            foreach ($redirects->filter(fn (int $pageId): bool => $pageId === $page->id)->keys() as $oldSlug) {
                $targets[$oldSlug] ??= $target;
            }
        }

        return array_intersect_key($targets, array_flip($slugs));
    }
}
