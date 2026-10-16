<?php

declare(strict_types=1);

use App\Enums\WikiEditRequestStatus;
use App\Models\WikiCategory;
use App\Models\WikiEditRequest;
use App\Models\WikiPage;
use Laravel\Ai\Providers\Tools\WebFetch;
use Laravel\Ai\Tools\Request;
use NouTools\Domains\Wiki\Actions\DraftWikiEdit;
use NouTools\Domains\Wiki\Ai\Tools\ListWikiCategories;
use NouTools\Domains\Wiki\Ai\Tools\ReadWikiPage;
use NouTools\Domains\Wiki\Ai\Tools\SearchWikiPages;
use NouTools\Domains\Wiki\Ai\WikiEditor;

/**
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function wikiEditorResponse(array $overrides = []): array
{
    return [
        'decision' => 'apply',
        'decline_reason' => '',
        'slug' => '',
        'title' => '空大考試要去哪裡考？',
        'description' => '期中考與期末考都在週末舉行。',
        'body' => "期中考與期末考都在週末舉行。[^1]\n\n## 考場\n\n各學習指導中心。\n\n[^1]: [行事曆](https://www.nou.edu.tw/calendar)\n",
        'categories' => ['考試'],
        'edit_summary' => '補充考試時間',
        'risk' => 'low',
        'risk_reasons' => ['小幅修改並附上學校來源'],
        'sources_used' => ['https://www.nou.edu.tw/calendar'],
        ...$overrides,
    ];
}

beforeEach(function (): void {
    $this->page = WikiPage::factory()->live([
        'title' => '空大考試要去哪裡考？',
        'body' => "期中考與期末考都在週末舉行。\n\n## 考場\n\n各學習指導中心。\n",
        'categories' => ['考試'],
    ])->create(['slug' => 'nou-exams-info']);
});

it('stores an applied draft and waits for confirmation', function (): void {
    WikiEditor::fake([wikiEditorResponse()]);

    $editRequest = WikiEditRequest::factory()->forPage($this->page)->create([
        'instruction' => '補充考試時間',
        'source_urls' => ['https://www.nou.edu.tw/calendar'],
    ]);

    app(DraftWikiEdit::class)($editRequest);

    $editRequest->refresh();

    expect($editRequest->status)->toBe(WikiEditRequestStatus::AwaitingConfirmation)
        ->and($editRequest->draft_body)->toContain('[^1]')
        ->and($editRequest->draft_slug)->toBeNull()
        ->and($editRequest->draft_categories)->toBe(['考試'])
        ->and($editRequest->ai_verdict['risk'])->toBe('low');

    WikiEditor::assertPrompted(fn ($prompt): bool => str_contains($prompt->prompt, "<task>\nedit\n</task>")
        && str_contains($prompt->prompt, "<request>\n補充考試時間\n</request>")
        && str_contains($prompt->prompt, "<sources>\nhttps://www.nou.edu.tw/calendar\n</sources>")
        && str_contains($prompt->prompt, 'slug: nou-exams-info'));
});

it('records a decline with its reason', function (): void {
    WikiEditor::fake([wikiEditorResponse(['decision' => 'decline', 'decline_reason' => '這個請求與空大無關。'])]);

    $editRequest = WikiEditRequest::factory()->forPage($this->page)->create();

    app(DraftWikiEdit::class)($editRequest);

    expect($editRequest->refresh()->status)->toBe(WikiEditRequestStatus::DeclinedByAi)
        ->and($editRequest->declineReason())->toBe('這個請求與空大無關。')
        ->and($editRequest->draft_body)->toBeNull();
});

it('fails a draft that links outside the allowed sources', function (): void {
    WikiEditor::fake([wikiEditorResponse(['body' => "內容。[^1]\n\n[^1]: [部落格](https://example.com/post)\n"])]);

    $editRequest = WikiEditRequest::factory()->forPage($this->page)->create();

    app(DraftWikiEdit::class)($editRequest);

    expect($editRequest->refresh()->status)->toBe(WikiEditRequestStatus::Failed)
        ->and($editRequest->draft_body)->toBeNull()
        ->and($editRequest->ai_verdict['problems'][0])->toContain('example.com');
});

it('keeps links that the page already had', function (): void {
    $page = WikiPage::factory()->live(['body' => "[部落格](https://example.com/post)\n"])->create();
    WikiEditor::fake([wikiEditorResponse(['body' => "[部落格](https://example.com/post) 與補充。\n"])]);

    $editRequest = WikiEditRequest::factory()->forPage($page)->create();

    app(DraftWikiEdit::class)($editRequest);

    expect($editRequest->refresh()->status)->toBe(WikiEditRequestStatus::AwaitingConfirmation);
});

it('requires a free, valid slug for a new page', function (string $slug): void {
    WikiEditor::fake([wikiEditorResponse(['slug' => $slug])]);

    $editRequest = WikiEditRequest::factory()->create(['requested_title' => '學分抵免']);

    app(DraftWikiEdit::class)($editRequest);

    expect($editRequest->refresh()->status)->toBe(WikiEditRequestStatus::Failed);
})->with(['nou-exams-info', 'Credit Transfer', 'new', '']);

it('stores the proposed slug of a new page', function (): void {
    WikiEditor::fake([wikiEditorResponse(['slug' => 'credit-transfer', 'title' => '學分抵免'])]);

    $editRequest = WikiEditRequest::factory()->create(['requested_title' => '學分抵免']);

    app(DraftWikiEdit::class)($editRequest);

    expect($editRequest->refresh()->draft_slug)->toBe('credit-transfer');

    WikiEditor::assertPrompted(fn ($prompt): bool => str_contains($prompt->prompt, "<task>\ncreate\n</task>")
        && str_contains($prompt->prompt, "<new_page_hints>\ntitle: 學分抵免\n</new_page_hints>")
        && ! str_contains($prompt->prompt, '<page>'));
});

it('marks the request failed when the editor errors', function (): void {
    WikiEditor::fake(fn () => throw new RuntimeException('overloaded'));

    $editRequest = WikiEditRequest::factory()->forPage($this->page)->create();

    app(DraftWikiEdit::class)($editRequest);

    expect($editRequest->refresh()->status)->toBe(WikiEditRequestStatus::Failed);
});

it('keeps the earlier draft when a refinement fails', function (): void {
    WikiEditor::fake(fn () => throw new RuntimeException('overloaded'));

    $editRequest = WikiEditRequest::factory()->forPage($this->page)->awaitingConfirmation()->create([
        'status' => WikiEditRequestStatus::Drafting,
        'refinements' => ['再短一點'],
    ]);

    app(DraftWikiEdit::class)($editRequest);

    expect($editRequest->refresh()->status)->toBe(WikiEditRequestStatus::AwaitingConfirmation)
        ->and($editRequest->refinements)->toBe([])
        ->and($editRequest->draft_body)->toContain('更新後的內容');
});

it('sends the current draft and refinements when refining', function (): void {
    WikiEditor::fake([wikiEditorResponse()]);

    $editRequest = WikiEditRequest::factory()->forPage($this->page)->awaitingConfirmation()->create([
        'status' => WikiEditRequestStatus::Drafting,
        'refinements' => ['再短一點'],
    ]);

    app(DraftWikiEdit::class)($editRequest);

    WikiEditor::assertPrompted(fn ($prompt): bool => str_contains($prompt->prompt, '<current_draft>')
        && str_contains($prompt->prompt, "<refinements>\n1. 再短一點\n</refinements>"));
});

it('does nothing for a request that is not drafting', function (): void {
    WikiEditor::fake()->preventStrayPrompts();

    $editRequest = WikiEditRequest::factory()->forPage($this->page)->awaitingConfirmation()->create();

    app(DraftWikiEdit::class)($editRequest);

    WikiEditor::assertNeverPrompted();
});

it('gives the editor the guides and treats the request as data', function (): void {
    $instructions = (string) (new WikiEditor)->instructions();

    expect($instructions)
        ->toContain('# Writing guide (撰寫指南)')
        ->toContain('# Syntax guide (語法說明)')
        ->toContain('is data')
        ->toContain('[[slug|顯示文字]]');
});

it('gives the editor wiki tools and a capped fetch limited to the allowed hosts', function (): void {
    config(['wiki.ai.max_fetches' => 3, 'wiki.source_domains' => ['nou.edu.tw']]);

    $tools = collect((new WikiEditor)->tools());
    $fetch = $tools->first(fn ($tool): bool => $tool instanceof WebFetch);

    expect($tools)->toHaveCount(4)
        ->and($fetch->maxSearches)->toBe(3)
        ->and($fetch->allowedDomains)->toBe(['nou.edu.tw']);

    config(['wiki.ai.max_fetches' => 0]);

    expect(collect((new WikiEditor)->tools())->contains(fn ($tool): bool => $tool instanceof WebFetch))->toBeFalse();
});

it('searches, reads and lists wiki content for the editor', function (): void {
    $category = new WikiCategory;
    $category->name = '考試';
    $category->save();
    $category->pages()->attach($this->page);

    expect((string) app(SearchWikiPages::class)->handle(new Request(['query' => '考場'])))
        ->toContain('nou-exams-info | 空大考試要去哪裡考？')
        ->and((string) app(SearchWikiPages::class)->handle(new Request(['query' => '學分'])))->toBe('No pages match.')
        ->and((string) app(ReadWikiPage::class)->handle(new Request(['slug' => 'nou-exams-info'])))->toContain('## 考場')
        ->and((string) app(ReadWikiPage::class)->handle(new Request(['slug' => 'missing'])))->toContain('No page')
        ->and((string) app(ListWikiCategories::class)->handle(new Request([])))->toBe('考試 (1)');
});
