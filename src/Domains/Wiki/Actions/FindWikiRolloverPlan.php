<?php

declare(strict_types=1);

namespace NouTools\Domains\Wiki\Actions;

use App\Enums\WikiSourceDocumentStatus;
use App\Models\WikiPage;
use App\Models\WikiSourceDocument;
use App\Models\WikiSourceSection;
use Illuminate\Support\Collection;
use NouTools\Domains\Wiki\Support\WikiRolloverPage;
use NouTools\Domains\Wiki\Support\WikiRolloverPlan;

/**
 * Works out which wiki pages a new semester's document makes stale. A page
 * counts as relying on the previous document when its current text links
 * that document's URL; the `#page=N` the editor cites says which sections it
 * relies on (every section that spans page N, since several can share one
 * page). A link without a page makes every section a candidate.
 *
 * Sections of the two documents are paired by title, ignoring the numbering
 * and semester words (`一、`, `附錄三`, `115 學年度上學期`), because section
 * keys shift when an item is added or removed. Pairs whose text differs are
 * `changed`, titles without a partner are `unmatched`. Identical text from
 * one semester to the next is rare (headers carry the term), so `changed`
 * means "worth a look", not "the facts differ".
 */
final readonly class FindWikiRolloverPlan
{
    public function __construct(private FindPreviousWikiSourceDocument $findPreviousWikiSourceDocument) {}

    public function __invoke(WikiSourceDocument $current): ?WikiRolloverPlan
    {
        $previous = ($this->findPreviousWikiSourceDocument)($current);

        if ($previous === null || $current->status !== WikiSourceDocumentStatus::Ready) {
            return null;
        }

        $oldSections = $previous->sections()->get();
        $statuses = $this->compare($oldSections, $current->sections()->get());
        $bareUrl = strtok($previous->url, '#');

        $pages = [];

        foreach ($this->pagesCiting((string) $bareUrl) as $page) {
            $planned = $this->planPage($page, (string) $bareUrl, $oldSections, $statuses);

            if ($planned !== null) {
                $pages[] = $planned;
            }
        }

        return new WikiRolloverPlan($previous, $current, $pages);
    }

    /**
     * @param  Collection<int, WikiSourceSection>  $oldSections
     * @param  array<string, array{status: string, newKey: string|null}>  $statuses  by old section key
     */
    private function planPage(WikiPage $page, string $url, Collection $oldSections, array $statuses): ?WikiRolloverPage
    {
        $body = (string) $page->currentRevision?->body;
        preg_match_all('~'.preg_quote($url, '~').'(?:#page=(\d+))?(?!\w)~', $body, $matches);

        // The SQL prefilter also matches longer urls that merely start with it.
        if ($matches[0] === []) {
            return null;
        }

        $pageKnown = true;
        $candidates = collect();

        foreach ($matches[1] as $cited) {
            $spanning = $cited === '' ? collect() : $oldSections->filter(
                fn (WikiSourceSection $section): bool => $section->page_start <= (int) $cited && (int) $cited <= $section->page_end,
            );

            if ($spanning->isEmpty()) {
                $pageKnown = false;
                $candidates = $oldSections;

                break;
            }

            $candidates = $candidates->merge($spanning);
        }

        return new WikiRolloverPage(
            $page,
            $candidates->unique('key')->values()->map(fn (WikiSourceSection $section): array => [
                'key' => $section->key,
                'title' => $section->title,
                ...$statuses[$section->key],
            ])->all(),
            $pageKnown,
        );
    }

    /**
     * @return Collection<int, WikiPage>
     */
    private function pagesCiting(string $url): Collection
    {
        $like = '%'.addcslashes($url, '%_\\').'%';

        return WikiPage::query()
            ->listed()
            ->with('currentRevision')
            ->whereHas('currentRevision', fn ($revision) => $revision->where('body', 'like', $like))
            ->orderBy('slug')
            ->get();
    }

    /**
     * @param  Collection<int, WikiSourceSection>  $old
     * @param  Collection<int, WikiSourceSection>  $new
     * @return array<string, array{status: string, newKey: string|null}>
     */
    private function compare(Collection $old, Collection $new): array
    {
        $partners = [];

        foreach ($new as $section) {
            $partners[$this->normalizeTitle($section->title)][] = $section;
        }

        $result = [];

        foreach ($old as $section) {
            $title = $this->normalizeTitle($section->title);
            $queue = $partners[$title] ?? [];
            $partner = array_shift($queue);
            $partners[$title] = $queue;

            $result[$section->key] = match (true) {
                $partner === null => ['status' => 'unmatched', 'newKey' => null],
                $partner->content_hash !== $section->content_hash => ['status' => 'changed', 'newKey' => $partner->key],
                default => ['status' => 'unchanged', 'newKey' => $partner->key],
            };
        }

        return $result;
    }

    private function normalizeTitle(string $title): string
    {
        $title = (string) preg_replace('/（第 \d+–\d+ 頁）$/u', '', $title);
        $title = (string) preg_replace('/^(?:附錄[一二三四五六七八九十廿]+|[一二三四五六七八九十廿]+、)\s*/u', '', $title);
        $title = (string) preg_replace('/\d{2,3}\s*學年度\s*[上下]學期/u', '', $title);

        return (string) preg_replace('/[\s\p{P}]+/u', '', $title);
    }
}
