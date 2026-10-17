<?php

declare(strict_types=1);

use App\Models\WikiPage;
use App\Models\WikiSourceDocument;
use App\Models\WikiSourceSection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use NouTools\Domains\Wiki\Actions\FindWikiRolloverPlan;

uses(RefreshDatabase::class);

$oldUrl = 'https://studadm.nou.edu.tw/files/114-2.pdf';

$section = function (WikiSourceDocument $document, string $key, string $title, string $body, int $start, ?int $end = null): void {
    WikiSourceSection::factory()->for($document, 'document')->create([
        'key' => $key,
        'title' => $title,
        'body' => $body,
        'content_hash' => hash('sha256', $body),
        'page_start' => $start,
        'page_end' => $end ?? $start,
        'position' => $start,
    ]);
};

beforeEach(function () use ($oldUrl, $section): void {
    $this->old = WikiSourceDocument::factory()->ready()->create(['term' => '114-2', 'url' => $oldUrl]);
    $this->new = WikiSourceDocument::factory()->ready()->create(['term' => '115-1', 'url' => 'https://studadm.nou.edu.tw/files/115-1.pdf']);

    // Same fees text, a changed 減免, an item that moved from 八 to 九 and a dropped one.
    $section($this->old, 'part-2', '二、註冊暨選課日期 114 學年度下學期', '日期不變', 3);
    $section($this->old, 'part-8', '八、申請減免學分學雜費相關事宜', '年長者 65 歲', 6, 10);
    $section($this->old, 'part-9', '九、選課面授地點之劃記', '地點', 10);
    $section($this->old, 'appendix-24', '附錄廿四 選課表', '表', 90);
    $section($this->new, 'part-2', '二、註冊暨選課日期 115 學年度上學期', '日期不變', 3);
    $section($this->new, 'part-9', '九、申請減免學分學雜費相關事宜', '年長者 65 歲，新增低收入戶', 6, 10);
    $section($this->new, 'part-10', '十、選課面授地點之劃記', '地點', 10);

    $this->page = fn (string $slug, string $body): WikiPage => WikiPage::factory()->live(['body' => $body])->create(['slug' => $slug]);
});

it('finds the previous document in the series', function () {
    $find = app(FindWikiRolloverPlan::class);
    $newer = WikiSourceDocument::factory()->ready()->create(['term' => '115-2']);
    WikiSourceDocument::factory()->create(['term' => '113-2', 'status' => 'failed']);
    $other = WikiSourceDocument::factory()->ready()->create(['series' => 'exam-schedule', 'term' => '115-1']);

    expect($find->previousDocument($newer)->is($this->new))->toBeTrue()
        ->and($find->previousDocument($this->new)->is($this->old))->toBeTrue()
        ->and($find->previousDocument($this->old))->toBeNull()
        ->and($find->previousDocument($other))->toBeNull();
});

it('finds the previous version of an evergreen document by order', function () {
    $first = WikiSourceDocument::factory()->ready()->create(['series' => 'academic-regulations', 'term' => null]);
    $second = WikiSourceDocument::factory()->ready()->create(['series' => 'academic-regulations', 'term' => null]);

    expect(app(FindWikiRolloverPlan::class)->previousDocument($second)->is($first))->toBeTrue()
        ->and(app(FindWikiRolloverPlan::class)->previousDocument($first))->toBeNull();
});

it('pairs sections by title, so a section that moved from 八 to 九 is compared with its new self', function () use ($oldUrl) {
    ($this->page)('fees', "年長者免繳。[^1]\n\n[^1]: [注意事項]({$oldUrl}#page=6)\n");

    $plan = app(FindWikiRolloverPlan::class)($this->new);
    $page = $plan->affected()[0];

    expect($plan->unaffected())->toBe([])
        ->and($page->page->slug)->toBe('fees')
        ->and($page->pageKnown)->toBeTrue()
        ->and($page->sections)->toBe([
            ['key' => 'part-8', 'title' => '八、申請減免學分學雜費相關事宜', 'status' => 'changed', 'newKey' => 'part-9'],
        ]);
});

it('treats a page that only cites unchanged sections as unaffected', function () use ($oldUrl) {
    ($this->page)('dates', "日期。[^1]\n\n[^1]: [注意事項]({$oldUrl}#page=3)\n");

    $plan = app(FindWikiRolloverPlan::class)($this->new);

    expect($plan->affected())->toBe([])
        ->and($plan->unaffected())->toHaveCount(1)
        ->and($plan->unaffected()[0]->sections[0])->toMatchArray(['key' => 'part-2', 'status' => 'unchanged', 'newKey' => 'part-2']);
});

it('reports a section with no partner as unmatched', function () use ($oldUrl) {
    ($this->page)('form', "選課表。[^1]\n\n[^1]: [注意事項]({$oldUrl}#page=90)\n");

    $page = app(FindWikiRolloverPlan::class)($this->new)->affected()[0];

    expect($page->sections[0])->toMatchArray(['key' => 'appendix-24', 'status' => 'unmatched', 'newKey' => null]);
});

it('treats every section a cited page spans as a candidate', function () use ($oldUrl) {
    // Page 10 is shared by part-8 (pp. 6-10) and part-9 (p. 10).
    ($this->page)('shared', "事項。[^1]\n\n[^1]: [注意事項]({$oldUrl}#page=10)\n");

    $page = app(FindWikiRolloverPlan::class)($this->new)->affected()[0];

    expect(array_column($page->sections, 'key'))->toBe(['part-8', 'part-9']);
});

it('makes every section a candidate when the link has no page or an unknown one', function (string $suffix) use ($oldUrl) {
    ($this->page)('bare', "事項。[^1]\n\n[^1]: [注意事項]({$oldUrl}{$suffix})\n");

    $page = app(FindWikiRolloverPlan::class)($this->new)->affected()[0];

    expect($page->pageKnown)->toBeFalse()
        ->and(array_column($page->sections, 'key'))->toBe(['part-2', 'part-8', 'part-9', 'appendix-24']);
})->with(['no page' => '', 'page outside the document' => '#page=500']);

it('ignores pages that cite another document or only share a url prefix', function () use ($oldUrl) {
    ($this->page)('other', "事項。[^1]\n\n[^1]: [別份](https://studadm.nou.edu.tw/files/other.pdf#page=6)\n");
    ($this->page)('prefix', "事項。[^1]\n\n[^1]: [別份]({$oldUrl}x#page=6)\n");

    expect(app(FindWikiRolloverPlan::class)($this->new)->pages)->toBe([]);
});

it('has no plan without a previous document or while the new one is not ready', function () {
    $find = app(FindWikiRolloverPlan::class);

    expect($find($this->old))->toBeNull();

    $this->new->forceFill(['status' => 'failed'])->save();

    expect($find($this->new->refresh()))->toBeNull();
});
