<?php

declare(strict_types=1);

namespace NouTools\Domains\Wiki\Actions;

use App\Models\WikiCategory;
use App\Models\WikiLink;
use App\Models\WikiPage;

/**
 * Rebuilds a page's category membership and outgoing `[[links]]` from its
 * current revision. These tables are derived and never edited directly.
 */
final readonly class SyncWikiPageIndexes
{
    public function __construct(private InspectWikiMarkdown $inspectWikiMarkdown) {}

    public function __invoke(WikiPage $page): void
    {
        $revision = $page->currentRevision()->first();

        $categoryIds = collect($revision->categories ?? [])
            ->map(fn (string $name): int => $this->findOrCreateCategory($name)->id)
            ->all();

        $page->categories()->sync($categoryIds);

        WikiCategory::query()->whereDoesntHave('pages')->delete();

        WikiLink::query()->where('from_page_id', $page->id)->delete();

        $slugs = $revision === null ? [] : ($this->inspectWikiMarkdown)($revision->body)['wikiLinks'];

        WikiLink::query()->insert(array_map(
            fn (string $slug): array => ['from_page_id' => $page->id, 'to_slug' => $slug],
            array_values(array_diff($slugs, [$page->slug])),
        ));
    }

    private function findOrCreateCategory(string $name): WikiCategory
    {
        $category = WikiCategory::query()->where('name', $name)->first();

        if ($category === null) {
            $category = new WikiCategory;
            $category->name = $name;
            $category->saveOrFail();
        }

        return $category;
    }
}
