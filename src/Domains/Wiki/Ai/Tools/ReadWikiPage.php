<?php

declare(strict_types=1);

namespace NouTools\Domains\Wiki\Ai\Tools;

use App\Models\WikiPage;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use NouTools\Domains\Wiki\Markdown\WikiPageResolver;
use Stringable;

/**
 * Lets the editor read another page, e.g. to keep two pages consistent or to
 * link to the right section with `[[slug#heading|label]]`.
 */
final readonly class ReadWikiPage implements Tool
{
    public function __construct(private WikiPageResolver $resolver) {}

    public function name(): string
    {
        return 'read_wiki_page';
    }

    public function description(): Stringable|string
    {
        return 'Read the current Markdown of another wiki page by its slug. Returns its title, description, categories and body.';
    }

    public function handle(Request $request): Stringable|string
    {
        $slug = trim((string) $request->string('slug'));
        $target = $this->resolver->resolve([$slug])[$slug] ?? null;

        $page = $target === null
            ? null
            : WikiPage::query()->live()->where('slug', $target->slug)->with('currentRevision')->first();

        if ($page === null || $page->currentRevision === null) {
            return "No page with slug \"{$slug}\".";
        }

        $revision = $page->currentRevision;

        return implode("\n", [
            "slug: {$page->slug}",
            "title: {$revision->title}",
            'description: '.(string) $revision->description,
            'categories: '.implode(', ', $revision->categories),
            '',
            $revision->body,
        ]);
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'slug' => $schema->string()->description('The page slug, e.g. nou-exams-info.')->required(),
        ];
    }
}
