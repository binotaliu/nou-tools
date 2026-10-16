<?php

declare(strict_types=1);

namespace NouTools\Domains\Wiki\Actions;

use App\Models\WikiPage;
use App\Models\WikiRevision;
use NouTools\Domains\Wiki\PageData\WikiRevisionPageData;
use NouTools\Domains\Wiki\ViewModels\WikiDiffLineViewModel;
use NouTools\Domains\Wiki\ViewModels\WikiPageLinkViewModel;
use NouTools\Domains\Wiki\ViewModels\WikiRevisionSummaryViewModel;

/**
 * One revision with what changed since its parent and, for AI edits, the
 * visitor's request and sources (the form says they are public).
 */
final readonly class ShowWikiRevisionPage
{
    public function __construct(private RenderWikiMarkdown $renderWikiMarkdown) {}

    public function __invoke(WikiPage $page, WikiRevision $revision): WikiRevisionPageData
    {
        $page->loadMissing('currentRevision');
        $revision->loadMissing(['parent', 'editRequest']);

        return new WikiRevisionPageData(
            page: WikiPageLinkViewModel::fromModel($page),
            revision: WikiRevisionSummaryViewModel::fromModel($revision, $page->slug, $page->current_revision_id),
            instruction: $revision->editRequest?->instruction,
            sourceUrls: $revision->editRequest->source_urls ?? [],
            content: ($this->renderWikiMarkdown)($revision->body),
            diff: WikiDiffLineViewModel::between((string) $revision->parent?->body, $revision->body),
            isFirstRevision: $revision->parent === null,
        );
    }
}
