<?php

declare(strict_types=1);

use App\Enums\WikiEditRequestStatus;
use App\Jobs\GenerateWikiEditDraft;
use App\Models\WikiEditRequest;
use App\Models\WikiPage;
use App\Models\WikiSourceDocument;
use App\Models\WikiSourceSection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use NouTools\Domains\Wiki\Actions\DraftAndSubmitWikiEdit;
use NouTools\Domains\Wiki\Actions\QueueWikiRolloverEdits;
use NouTools\Domains\Wiki\Ai\WikiEditor;

uses(RefreshDatabase::class);

$oldUrl = 'https://studadm.nou.edu.tw/files/114-2.pdf';

beforeEach(function () use ($oldUrl): void {
    config(['wiki.source_domains' => ['nou.edu.tw']]);

    $this->old = WikiSourceDocument::factory()->ready()->create(['term' => '114-2', 'title' => '114 下選課注意事項', 'url' => $oldUrl]);
    $this->new = WikiSourceDocument::factory()->ready()->create(['term' => '115-1', 'title' => '115 上選課注意事項', 'url' => 'https://studadm.nou.edu.tw/files/115-1.pdf']);

    foreach ([[$this->old, '年長者 65 歲'], [$this->new, '年長者 65 歲，新增低收入戶']] as [$document, $body]) {
        WikiSourceSection::factory()->for($document, 'document')->create(['key' => 'part-8', 'title' => '八、減免', 'body' => $body, 'content_hash' => hash('sha256', $body), 'page_start' => 6, 'page_end' => 6]);
    }

    foreach ([[$this->old, '日期不變'], [$this->new, '日期不變']] as [$document, $body]) {
        WikiSourceSection::factory()->for($document, 'document')->create(['key' => 'part-2', 'title' => '二、日期', 'body' => $body, 'content_hash' => hash('sha256', $body), 'page_start' => 3, 'page_end' => 3]);
    }

    $this->fees = WikiPage::factory()->live(['body' => "年長者免繳。[^1]\n\n[^1]: [注意事項]({$oldUrl}#page=6)\n", 'categories' => ['選課']])->create(['slug' => 'fees']);
    $this->dates = WikiPage::factory()->live(['body' => "日期。[^1]\n\n[^1]: [注意事項]({$oldUrl}#page=3)\n", 'categories' => ['選課']])->create(['slug' => 'dates']);
});

it('creates a request and queues the editor for each page that relies on a changed section', function () {
    Bus::fake();

    $result = app(QueueWikiRolloverEdits::class)($this->new);

    $editRequest = WikiEditRequest::query()->sole();

    expect($result)->toBe(['queued' => 1, 'skipped' => 0])
        ->and($editRequest)
        ->wiki_page_id->toBe($this->fees->id)
        ->base_revision_id->toBe($this->fees->current_revision_id)
        ->wiki_source_document_id->toBe($this->new->id)
        ->student_schedule_id->toBeNull()
        ->ip_hash->toBeNull()
        ->status->toBe(WikiEditRequestStatus::Drafting)
        ->source_urls->toBe([$this->new->url])
        ->isRollover()->toBeTrue()
        ->and($editRequest->instruction)
        ->toContain('「114 下選課注意事項」（114-2）')
        ->toContain('「115 上選課注意事項」（115-1）')
        ->toContain('term 115-1 與 114-2')
        ->toContain('- part-8 八、減免 → part-8')
        ->not->toContain('part-2');
    Bus::assertDispatchedTimes(GenerateWikiEditDraft::class, 1);
});

it('does not queue a page twice while its request is open', function () {
    Bus::fake();

    app(QueueWikiRolloverEdits::class)($this->new);
    $second = app(QueueWikiRolloverEdits::class)($this->new);

    expect($second)->toBe(['queued' => 0, 'skipped' => 1])
        ->and(WikiEditRequest::query()->count())->toBe(1);

    WikiEditRequest::query()->update(['status' => WikiEditRequestStatus::Published]);

    expect(app(QueueWikiRolloverEdits::class)($this->new))->toBe(['queued' => 1, 'skipped' => 0]);
});

it('says which sections to compare when the citation has no page', function () use ($oldUrl) {
    Bus::fake();
    $this->fees->revisions()->getQuery()->update(['body' => "年長者免繳。[^1]\n\n[^1]: [注意事項]({$oldUrl})\n"]);

    app(QueueWikiRolloverEdits::class)($this->new);

    expect(WikiEditRequest::query()->sole()->instruction)
        ->toContain('沒有指定頁碼')
        ->toContain('part-8')
        ->not->toContain('part-2');
});

it('caps the sections listed in the instruction', function () {
    Bus::fake();

    foreach (range(1, 15) as $number) {
        WikiSourceSection::factory()->for($this->old, 'document')->create(['key' => "extra-{$number}", 'title' => "額外段落{$number}", 'body' => 'a', 'content_hash' => hash('sha256', 'a'), 'page_start' => 6, 'page_end' => 6]);
    }

    app(QueueWikiRolloverEdits::class)($this->new);

    expect(WikiEditRequest::query()->sole()->instruction)->toContain('另有 4 段');
});

it('refuses a document with nothing to compare against', function () {
    Bus::fake();

    app(QueueWikiRolloverEdits::class)($this->old);
})->throws(DomainException::class, '上一版');

it('sends a drafted rollover to review instead of publishing it', function () {
    Bus::fake();
    app(QueueWikiRolloverEdits::class)($this->new);
    $editRequest = WikiEditRequest::query()->sole();

    WikiEditor::fake([[
        'decision' => 'apply',
        'decline_reason' => '',
        'slug' => '',
        'title' => $this->fees->currentRevision->title,
        'description' => '摘要',
        'body' => "年長者與低收入戶免繳。[^1]\n\n[^1]: [115 上選課注意事項](https://studadm.nou.edu.tw/files/115-1.pdf#page=6)\n",
        'categories' => ['選課'],
        'edit_summary' => '更新 115 學年度上學期減免',
        'risk' => 'low',
        'risk_reasons' => ['小幅修改並附上學校來源'],
        'sources_used' => ['https://studadm.nou.edu.tw/files/115-1.pdf#page=6'],
    ]]);

    app(DraftAndSubmitWikiEdit::class)($editRequest);

    $editRequest->refresh();

    expect($editRequest->status)->toBe(WikiEditRequestStatus::PendingReview)
        ->and($editRequest->ai_verdict['reviewReasons'])->toContain('換學期更新');
    WikiEditor::assertPrompted(fn ($prompt): bool => str_contains($prompt->prompt, '<documents>')
        && str_contains($prompt->prompt, 'cited by the visitor')
        && str_contains($prompt->prompt, '換學期更新'));
});
