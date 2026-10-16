<?php

declare(strict_types=1);

namespace NouTools\Domains\Wiki\Actions;

use App\Models\WikiPage;
use App\Models\WikiRevision;
use App\Models\WikiSlugRedirect;
use DomainException;
use Illuminate\Support\Facades\DB;
use NouTools\Domains\Wiki\DataTransferObjects\PublishWikiRevisionDTO;

/**
 * Adds a revision to a page (creating the page when it has none yet), makes
 * it current and rebuilds the page's indexes. A slug change leaves a
 * redirect behind, so old URLs and `[[old-slug]]` links keep working.
 */
final readonly class PublishWikiRevision
{
    public function __construct(private SyncWikiPageIndexes $syncWikiPageIndexes) {}

    /**
     * @param  WikiPage|null  $page  null creates a new page under $slug
     *
     * @throws DomainException when the slug is invalid or taken
     */
    public function __invoke(?WikiPage $page, PublishWikiRevisionDTO $data, ?string $slug = null): WikiRevision
    {
        return DB::transaction(function () use ($page, $data, $slug): WikiRevision {
            $page = $page === null ? $this->createPage((string) $slug) : $this->renameIfNeeded($page, $slug);

            $revision = new WikiRevision;
            $revision->fillFromDTO($data);
            $revision->wiki_page_id = $page->id;
            $revision->parent_revision_id = $page->current_revision_id;
            $revision->saveOrFail();

            $page->current_revision_id = $revision->id;
            $page->saveOrFail();

            ($this->syncWikiPageIndexes)($page);

            return $revision;
        });
    }

    private function createPage(string $slug): WikiPage
    {
        if (! WikiPage::isSlugAvailable($slug)) {
            throw new DomainException("網址代稱「{$slug}」無效或已被使用。");
        }

        $page = new WikiPage;
        $page->slug = $slug;
        $page->saveOrFail();

        return $page;
    }

    private function renameIfNeeded(WikiPage $page, ?string $slug): WikiPage
    {
        if ($slug === null || $slug === $page->slug) {
            return $page;
        }

        // Taking back one of its own old slugs is fine; anything else must be free.
        $ownRedirect = WikiSlugRedirect::query()->where('old_slug', $slug)->where('wiki_page_id', $page->id)->first();

        if ($ownRedirect === null && ! WikiPage::isSlugAvailable($slug)) {
            throw new DomainException("網址代稱「{$slug}」無效或已被使用。");
        }

        $ownRedirect?->delete();

        $redirect = new WikiSlugRedirect;
        $redirect->old_slug = $page->slug;
        $redirect->wiki_page_id = $page->id;
        $redirect->saveOrFail();

        $page->slug = $slug;

        return $page;
    }
}
