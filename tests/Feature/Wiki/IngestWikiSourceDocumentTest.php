<?php

declare(strict_types=1);

use App\Enums\WikiSourceDocumentStatus;
use App\Jobs\FetchWikiSourceDocument;
use App\Models\WikiSourceDocument;
use App\Models\WikiSourceSection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;
use NouTools\Domains\Wiki\Actions\CreateWikiSourceDocument;
use NouTools\Domains\Wiki\Actions\IngestWikiSourceDocument;
use NouTools\Domains\Wiki\Actions\QueueWikiSourceDocumentFetch;
use NouTools\Domains\Wiki\DataTransferObjects\FetchWikiSourceDocumentDTO;

uses(RefreshDatabase::class);

const SOURCE_URL = 'https://studadm.nou.edu.tw/files/notes.pdf';

function noticeHtml(string $fees = '每學分 940 元。'): string
{
    return '<p>一、註冊方式：</p><p>臨櫃辦理。</p><p>二、費用：</p><p>'.$fees.'</p><p>【附錄一】</p><p>課程表</p><p>甲課程</p>';
}

/**
 * Each fetch of the notice gets the next response: an html string, or an HTTP status.
 */
function fakeNotice(string|int ...$responses): void
{
    $sequence = Http::sequence();

    foreach ($responses as $response) {
        $sequence->push(...is_int($response) ? ['', $response] : [$response, 200, ['Content-Type' => 'text/html']]);
    }

    Http::fake([SOURCE_URL => $sequence]);
}

beforeEach(function (): void {
    config(['wiki.source_domains' => ['nou.edu.tw']]);
});

it('fetches, splits and stores the sections and marks the document ready', function () {
    fakeNotice(noticeHtml());
    $document = WikiSourceDocument::factory()->create(['url' => SOURCE_URL]);

    $result = app(IngestWikiSourceDocument::class)($document);

    $document->refresh();

    expect($document->status)->toBe(WikiSourceDocumentStatus::Ready)
        ->and($document->failure_reason)->toBeNull()
        ->and($document->fetched_at)->not->toBeNull()
        ->and($document->page_count)->toBe(1)
        ->and($document->sections->pluck('key')->all())->toBe(['part-1', 'part-2', 'appendix-1'])
        ->and($document->sections[1]->body)->toContain('每學分 940 元')
        ->and($result)->toBe(['added' => ['part-1', 'part-2', 'appendix-1'], 'changed' => [], 'removed' => []]);
});

it('on a re-fetch reports changed sections and keeps unchanged ones as they are', function () {
    fakeNotice(noticeHtml(), noticeHtml('每學分 1000 元。'));
    $document = WikiSourceDocument::factory()->create(['url' => SOURCE_URL]);
    app(IngestWikiSourceDocument::class)($document);
    $unchangedId = $document->sections()->where('key', 'part-1')->value('id');

    $result = app(IngestWikiSourceDocument::class)($document);

    expect($result)->toBe(['added' => [], 'changed' => ['part-2'], 'removed' => []])
        ->and($document->sections()->where('key', 'part-1')->value('id'))->toBe($unchangedId)
        ->and($document->sections()->where('key', 'part-2')->value('body'))->toContain('1000');
});

it('removes sections that are gone from the new version', function () {
    fakeNotice(noticeHtml(), '<p>一、註冊方式：</p><p>臨櫃辦理。</p><p>【附錄一】</p><p>課程表</p>');
    $document = WikiSourceDocument::factory()->create(['url' => SOURCE_URL]);
    app(IngestWikiSourceDocument::class)($document);

    $result = app(IngestWikiSourceDocument::class)($document);

    expect($result['removed'])->toBe(['part-2'])
        ->and($document->sections()->pluck('key')->all())->toBe(['part-1', 'appendix-1']);
});

it('keeps earlier sections and records the reason when a re-fetch fails', function () {
    fakeNotice(noticeHtml(), 404);
    $document = WikiSourceDocument::factory()->create(['url' => SOURCE_URL]);
    app(IngestWikiSourceDocument::class)($document);

    $result = app(IngestWikiSourceDocument::class)($document);

    $document->refresh();

    expect($document->status)->toBe(WikiSourceDocumentStatus::Failed)
        ->and($document->failure_reason)->toContain('HTTP 404')
        ->and($document->sections)->toHaveCount(3)
        ->and($result)->toBe(['added' => [], 'changed' => [], 'removed' => []]);
});

it('fails a document whose url is not on an allowed host', function () {
    Http::fake();
    $document = WikiSourceDocument::factory()->create(['url' => 'https://evil.example.com/a.pdf']);

    app(IngestWikiSourceDocument::class)($document);

    expect($document->refresh()->status)->toBe(WikiSourceDocumentStatus::Failed)
        ->and($document->failure_reason)->toContain('not on an allowed host');
    Http::assertNothingSent();
});

it('fails a page with no text', function () {
    fakeNotice('<html><script>x()</script></html>');
    $document = WikiSourceDocument::factory()->create(['url' => SOURCE_URL]);

    app(IngestWikiSourceDocument::class)($document);

    expect($document->refresh()->status)->toBe(WikiSourceDocumentStatus::Failed)
        ->and(WikiSourceSection::query()->count())->toBe(0);
});

it('registers a document and queues its first fetch', function () {
    Bus::fake();

    $document = app(CreateWikiSourceDocument::class)(new FetchWikiSourceDocumentDTO(
        url: SOURCE_URL,
        series: 'course-selection-notes',
        term: '115-1',
        title: '115 學年度上學期選課注意事項',
    ));

    expect($document->status)->toBe(WikiSourceDocumentStatus::Pending)
        ->and($document->term)->toBe('115-1');
    Bus::assertDispatched(FetchWikiSourceDocument::class, fn (FetchWikiSourceDocument $job): bool => $job->document->is($document));
});

it('refuses a second document for the same series and term, evergreen included', function (?string $term) {
    Bus::fake();
    $dto = new FetchWikiSourceDocumentDTO(SOURCE_URL, 'course-selection-notes', $term, '標題');

    app(CreateWikiSourceDocument::class)($dto);
    app(CreateWikiSourceDocument::class)($dto);
})->with(['a term' => '115-1', 'no term' => null])->throws(DomainException::class);

it('queues a re-fetch and marks the document pending', function () {
    Bus::fake();
    $document = WikiSourceDocument::factory()->ready()->create(['url' => SOURCE_URL]);
    $document->forceFill(['status' => WikiSourceDocumentStatus::Failed, 'failure_reason' => '舊的原因'])->save();

    app(QueueWikiSourceDocumentFetch::class)($document);

    expect($document->refresh()->status)->toBe(WikiSourceDocumentStatus::Pending)
        ->and($document->failure_reason)->toBeNull();
    Bus::assertDispatched(FetchWikiSourceDocument::class);
});

it('runs the ingest when the job is handled', function () {
    fakeNotice(noticeHtml());
    $document = WikiSourceDocument::factory()->create(['url' => SOURCE_URL]);

    (new FetchWikiSourceDocument($document))->handle(app(IngestWikiSourceDocument::class));

    expect($document->refresh()->status)->toBe(WikiSourceDocumentStatus::Ready);
});

it('validates the dto', function (array $payload, string $field) {
    FetchWikiSourceDocumentDTO::validateAndCreate([
        'url' => SOURCE_URL, 'series' => 'course-selection-notes', 'term' => '115-1', 'title' => '標題',
        ...$payload,
    ]);
})->with([
    'host' => [['url' => 'https://evil.example.com/a.pdf'], 'url'],
    'series' => [['series' => 'Course Notes'], 'series'],
    'term' => [['term' => '115'], 'term'],
    'title' => [['title' => ''], 'title'],
])->throws(ValidationException::class);
