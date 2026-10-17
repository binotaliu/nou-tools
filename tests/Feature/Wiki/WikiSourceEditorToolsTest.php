<?php

declare(strict_types=1);

use App\Models\WikiEditRequest;
use App\Models\WikiPage;
use App\Models\WikiSourceDocument;
use App\Models\WikiSourceSection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Ai\Tools\Request;
use NouTools\Domains\Wiki\Actions\BuildWikiEditorPrompt;
use NouTools\Domains\Wiki\Actions\FindWikiSourceDocument;
use NouTools\Domains\Wiki\Ai\Tools\ListWikiSourceDocuments;
use NouTools\Domains\Wiki\Ai\Tools\ListWikiSourceSections;
use NouTools\Domains\Wiki\Ai\Tools\ReadWikiSourceSection;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->older = WikiSourceDocument::factory()->ready()->create(['term' => '114-2', 'title' => '114 下選課注意事項', 'url' => 'https://www.nou.edu.tw/114-2.pdf']);
    $this->current = WikiSourceDocument::factory()->ready()->create(['term' => '115-1', 'title' => '115 上選課注意事項', 'url' => 'https://www.nou.edu.tw/115-1.pdf']);
    WikiSourceSection::factory()->for($this->current, 'document')->create(['key' => 'part-1', 'title' => '一、註冊方式', 'body' => '臨櫃辦理。', 'page_start' => 3, 'page_end' => 3, 'position' => 0]);
    WikiSourceSection::factory()->for($this->current, 'document')->create(['key' => 'part-8', 'title' => '八、減免', 'body' => '年長者免繳。', 'page_start' => 6, 'page_end' => 10, 'position' => 1]);
    WikiSourceSection::factory()->for($this->older, 'document')->create(['key' => 'part-1', 'title' => '一、註冊方式', 'body' => '舊學期內容。', 'position' => 0]);
    WikiSourceDocument::factory()->create(['series' => 'exam-schedule', 'term' => '115-1']);
});

it('finds a document by term, or the current one without a term', function () {
    $find = app(FindWikiSourceDocument::class);

    expect($find('course-selection-notes')->is($this->current))->toBeTrue()
        ->and($find('course-selection-notes', '')->is($this->current))->toBeTrue()
        ->and($find('course-selection-notes', '114-2')->is($this->older))->toBeTrue()
        ->and($find('course-selection-notes', '113-1'))->toBeNull()
        ->and($find('exam-schedule'))->toBeNull();
});

it('lists the stored documents, marking current and older ones', function () {
    $list = (string) app(ListWikiSourceDocuments::class)->handle(new Request([]));

    expect($list)
        ->toContain('course-selection-notes | 115-1 | current | 115 上選課注意事項 | 2 sections')
        ->toContain('course-selection-notes | 114-2 | older | 114 下選課注意事項 | 1 sections')
        ->not->toContain('exam-schedule');
});

it('lists the sections of the current or a given term', function () {
    $tool = app(ListWikiSourceSections::class);

    expect((string) $tool->handle(new Request(['series' => 'course-selection-notes'])))
        ->toContain('url: https://www.nou.edu.tw/115-1.pdf')
        ->toContain('part-1 | 一、註冊方式 | p3 | 5 chars')
        ->toContain('part-8 | 八、減免 | p6-10')
        ->and((string) $tool->handle(new Request(['series' => 'course-selection-notes', 'term' => '114-2'])))->toContain('term: 114-2')
        ->and((string) $tool->handle(new Request(['series' => 'missing'])))->toContain('list_source_documents');
});

it('reads one section with the url and page to cite', function () {
    $tool = app(ReadWikiSourceSection::class);

    expect((string) $tool->handle(new Request(['series' => 'course-selection-notes', 'key' => 'part-8'])))
        ->toContain('cite: https://www.nou.edu.tw/115-1.pdf#page=6')
        ->toContain('pages: 6-10')
        ->toContain('年長者免繳。')
        ->and((string) $tool->handle(new Request(['series' => 'course-selection-notes', 'term' => '114-2', 'key' => 'part-1'])))->toContain('舊學期內容。')
        ->and((string) $tool->handle(new Request(['series' => 'course-selection-notes', 'key' => 'nope'])))->toContain('list_source_sections')
        ->and((string) $tool->handle(new Request(['series' => 'missing', 'key' => 'part-1'])))->toContain('list_source_documents');
});

it('puts a catalog of stored documents in the prompt, with sections for the current one only', function () {
    $page = WikiPage::factory()->live(['title' => '選課', 'body' => "內容\n"])->create(['slug' => 'course-selection']);
    $editRequest = WikiEditRequest::factory()->forPage($page)->create(['source_urls' => ['https://www.nou.edu.tw/115-1.pdf']]);

    $prompt = app(BuildWikiEditorPrompt::class)($editRequest);

    expect($prompt)
        ->toContain("<documents>\ncourse-selection-notes | 115-1 | current | 115 上選課注意事項\nhttps://www.nou.edu.tw/115-1.pdf\ncited by the visitor\nsections:\n- part-1 | 一、註冊方式 | p3\n- part-8 | 八、減免 | p6-10")
        ->toContain("course-selection-notes | 114-2 | older | 114 下選課注意事項\nhttps://www.nou.edu.tw/114-2.pdf\nsections: see list_source_sections")
        ->not->toContain('舊學期內容')
        ->not->toContain('exam-schedule');
});

it('leaves the documents block out when nothing is stored', function () {
    WikiSourceDocument::query()->delete();
    $page = WikiPage::factory()->live()->create(['slug' => 'course-selection']);

    expect(app(BuildWikiEditorPrompt::class)(WikiEditRequest::factory()->forPage($page)->create()))->not->toContain('<documents>');
});
