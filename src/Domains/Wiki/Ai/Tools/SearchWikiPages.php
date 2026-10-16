<?php

declare(strict_types=1);

namespace NouTools\Domains\Wiki\Ai\Tools;

use App\Models\WikiPage;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

/**
 * Lets the editor find existing pages to link to with `[[slug]]`, and spot
 * a duplicate before writing a new page.
 */
final class SearchWikiPages implements Tool
{
    private const int LIMIT = 10;

    public function name(): string
    {
        return 'search_wiki_pages';
    }

    public function description(): Stringable|string
    {
        return 'Search the wiki\'s live pages by keyword (matches title, description and body). Returns up to 10 pages as "slug | title | description" lines. Use it to find pages to link with [[slug]] and to check whether a requested new page already exists.';
    }

    public function handle(Request $request): Stringable|string
    {
        $query = trim((string) $request->string('query'));

        if ($query === '') {
            return 'Empty query.';
        }

        $like = '%'.addcslashes($query, '%_\\').'%';

        $pages = WikiPage::query()
            ->live()
            ->join('wiki_revisions', 'wiki_revisions.id', '=', 'wiki_pages.current_revision_id')
            ->where(fn ($where) => $where
                ->where('wiki_revisions.title', 'like', $like)
                ->orWhere('wiki_revisions.description', 'like', $like)
                ->orWhere('wiki_revisions.body', 'like', $like))
            ->orderByRaw('case when wiki_revisions.title like ? then 0 else 1 end', [$like])
            ->limit(self::LIMIT)
            ->get(['wiki_pages.slug', 'wiki_revisions.title', 'wiki_revisions.description']);

        if ($pages->isEmpty()) {
            return 'No pages match.';
        }

        return $pages
            ->map(fn (WikiPage $page): string => implode(' | ', [$page->slug, $page->getAttribute('title'), (string) $page->getAttribute('description')]))
            ->implode("\n");
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'query' => $schema->string()->description('A keyword or short phrase, usually in Traditional Chinese.')->required(),
        ];
    }
}
