<?php

declare(strict_types=1);

namespace NouTools\Domains\Wiki\Ai\Tools;

use App\Models\WikiCategory;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

/**
 * Lets the editor reuse existing categories instead of inventing near-duplicates.
 */
final class ListWikiCategories implements Tool
{
    public function name(): string
    {
        return 'list_wiki_categories';
    }

    public function description(): Stringable|string
    {
        return 'List every existing wiki category with the number of pages in it, as "name (count)" lines.';
    }

    public function handle(Request $request): Stringable|string
    {
        $categories = WikiCategory::query()->withCount('pages')->orderByDesc('pages_count')->orderBy('name')->get();

        if ($categories->isEmpty()) {
            return 'There are no categories yet.';
        }

        return $categories
            ->map(fn (WikiCategory $category): string => "{$category->name} ({$category->getAttribute('pages_count')})")
            ->implode("\n");
    }

    public function schema(JsonSchema $schema): array
    {
        return [];
    }
}
