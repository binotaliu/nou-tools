<?php

declare(strict_types=1);

use App\Enums\UserRole;
use App\Enums\WikiSourceDocumentStatus;
use App\Filament\Resources\WikiEditRequests\Pages\ViewWikiEditRequest;
use App\Filament\Resources\WikiSourceDocuments\Pages\CreateWikiSourceDocument;
use App\Filament\Resources\WikiSourceDocuments\Pages\EditWikiSourceDocument;
use App\Filament\Resources\WikiSourceDocuments\Pages\ListWikiSourceDocuments;
use App\Filament\Resources\WikiSourceDocuments\RelationManagers\SectionsRelationManager;
use App\Jobs\FetchWikiSourceDocument;
use App\Models\User;
use App\Models\WikiEditRequest;
use App\Models\WikiPage;
use App\Models\WikiSourceDocument;
use App\Models\WikiSourceSection;
use Filament\Actions\Testing\TestAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Livewire\Livewire;
use NouTools\Domains\Wiki\Actions\FindWikiRolloverPlan;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

uses(RefreshDatabase::class);

$rolloverDocuments = function (): array {
    $old = WikiSourceDocument::factory()->ready()->create(['term' => '114-2', 'url' => 'https://www.nou.edu.tw/114-2.pdf']);
    $new = WikiSourceDocument::factory()->ready()->create(['term' => '115-1']);

    foreach ([[$old, '舊'], [$new, '新']] as [$document, $body]) {
        WikiSourceSection::factory()->for($document, 'document')->create(['key' => 'part-8', 'title' => '八、減免', 'body' => $body, 'content_hash' => hash('sha256', $body), 'page_start' => 6, 'page_end' => 6]);
    }

    return [$old, $new];
};

beforeEach(function (): void {
    config(['wiki.source_domains' => ['nou.edu.tw']]);

    $this->admin = User::factory()->create(['roles' => [UserRole::Admin->value]]);
    actingAs($this->admin);
});

it('is closed to non-admins', function () {
    actingAs(User::factory()->create());

    get('/admin/wiki-source-documents')->assertForbidden();
});

it('lists documents with their status', function () {
    $documents = WikiSourceDocument::factory()->count(2)->ready()->sequence(['term' => '114-2'], ['term' => '115-1'])->create();

    Livewire::test(ListWikiSourceDocuments::class)
        ->assertCanSeeTableRecords($documents);
});

it('creates a document and queues its fetch', function () {
    Bus::fake();

    Livewire::test(CreateWikiSourceDocument::class)
        ->fillForm([
            'url' => 'https://studadm.nou.edu.tw/files/notes.pdf',
            'title' => '115 學年度上學期選課注意事項',
            'series' => 'course-selection-notes',
            'term' => '115-1',
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $document = WikiSourceDocument::query()->sole();

    expect($document)->series->toBe('course-selection-notes')
        ->term->toBe('115-1')
        ->status->toBe(WikiSourceDocumentStatus::Pending);
    Bus::assertDispatched(FetchWikiSourceDocument::class);
});

it('creates an evergreen document without a term', function () {
    Bus::fake();

    Livewire::test(CreateWikiSourceDocument::class)
        ->fillForm([
            'url' => 'https://www.nou.edu.tw/files/rules.pdf',
            'title' => '學則',
            'series' => 'academic-regulations',
            'is_evergreen' => true,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    expect(WikiSourceDocument::query()->sole()->term)->toBeNull();
});

it('rejects a url on another host, a bad series, a missing term and a duplicate', function () {
    Bus::fake();
    WikiSourceDocument::factory()->create(['series' => 'course-selection-notes', 'term' => '115-1']);

    Livewire::test(CreateWikiSourceDocument::class)
        ->fillForm([
            'url' => 'https://evil.example.com/a.pdf',
            'title' => '標題',
            'series' => 'Course Notes',
            'term' => null,
        ])
        ->call('create')
        ->assertHasFormErrors(['url', 'series', 'term']);

    Livewire::test(CreateWikiSourceDocument::class)
        ->fillForm([
            'url' => 'https://www.nou.edu.tw/a.pdf',
            'title' => '標題',
            'series' => 'course-selection-notes',
            'term' => '115-1',
        ])
        ->call('create')
        ->assertHasFormErrors(['series']);

    Bus::assertNotDispatched(FetchWikiSourceDocument::class);
});

it('queues a re-fetch from the edit page', function () {
    Bus::fake();
    $document = WikiSourceDocument::factory()->ready()->create();

    Livewire::test(EditWikiSourceDocument::class, ['record' => $document->id])
        ->callAction('refetch')
        ->assertNotified();

    expect($document->refresh()->status)->toBe(WikiSourceDocumentStatus::Pending);
    Bus::assertDispatched(FetchWikiSourceDocument::class);
});

it('queues a re-fetch from the list', function () {
    Bus::fake();
    $document = WikiSourceDocument::factory()->ready()->create();

    Livewire::test(ListWikiSourceDocuments::class)
        ->callAction(TestAction::make('refetch')->table($document))
        ->assertNotified();

    Bus::assertDispatched(FetchWikiSourceDocument::class);
});

it('does not offer a re-fetch while the document is being fetched', function () {
    $document = WikiSourceDocument::factory()->create(['status' => WikiSourceDocumentStatus::Fetching]);

    Livewire::test(EditWikiSourceDocument::class, ['record' => $document->id])
        ->assertActionHidden('refetch');
});

it('edits the url, title and dates but never the series or term', function () {
    $document = WikiSourceDocument::factory()->ready()->create(['series' => 'course-selection-notes', 'term' => '115-1']);

    Livewire::test(EditWikiSourceDocument::class, ['record' => $document->id])
        ->fillForm([
            'title' => '新的標題',
            'url' => 'https://www.nou.edu.tw/new.pdf',
            'effective_to' => '2027-01-31',
            'series' => 'something-else',
            'term' => '999-9',
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($document->refresh())
        ->title->toBe('新的標題')
        ->url->toBe('https://www.nou.edu.tw/new.pdf')
        ->effective_to->toDateString()->toBe('2027-01-31')
        ->series->toBe('course-selection-notes')
        ->term->toBe('115-1');
});

it('lists sections and lets the key and title be corrected', function () {
    $document = WikiSourceDocument::factory()->ready()->create();
    $sections = WikiSourceSection::factory()->for($document, 'document')->count(2)->sequence(
        ['key' => 'part-1', 'position' => 0],
        ['key' => 'part-2', 'position' => 1],
    )->create();

    Livewire::test(SectionsRelationManager::class, ['ownerRecord' => $document, 'pageClass' => EditWikiSourceDocument::class])
        ->assertCanSeeTableRecords($sections)
        ->callAction(TestAction::make('edit')->table($sections->first()), ['key' => 'registration', 'title' => '註冊方式'])
        ->assertHasNoFormErrors()
        ->callAction(TestAction::make('edit')->table($sections->first()), ['key' => 'part-2', 'title' => '註冊方式'])
        ->assertHasFormErrors(['key']);

    expect($sections->first()->refresh())->key->toBe('registration')->title->toBe('註冊方式');
});

it('hides the rollover action for a document with no earlier version', function () {
    $document = WikiSourceDocument::factory()->ready()->create(['term' => '115-1']);

    Livewire::test(EditWikiSourceDocument::class, ['record' => $document->id])
        ->assertActionHidden('rollover');
});

it('queues a request for each page a rollover touches', function () use ($rolloverDocuments) {
    Bus::fake();
    [$old, $new] = $rolloverDocuments();
    WikiPage::factory()->live(['title' => '減免說明', 'body' => "免繳。[^1]\n\n[^1]: [注意事項](https://www.nou.edu.tw/114-2.pdf#page=6)\n"])->create(['slug' => 'fees']);

    Livewire::test(EditWikiSourceDocument::class, ['record' => $new->id])
        ->assertActionVisible('rollover')
        ->callAction('rollover')
        ->assertNotified('已為 1 個條目建立修改請求');

    expect(WikiEditRequest::query()->sole()->isRollover())->toBeTrue();
});

it('previews the pages a rollover touches and the ones it leaves alone', function () use ($rolloverDocuments) {
    [$old, $new] = $rolloverDocuments();
    WikiSourceSection::factory()->for($old, 'document')->create(['key' => 'part-2', 'title' => '二、日期', 'body' => 'x', 'content_hash' => hash('sha256', 'x'), 'page_start' => 3, 'page_end' => 3]);
    WikiSourceSection::factory()->for($new, 'document')->create(['key' => 'part-2', 'title' => '二、日期', 'body' => 'x', 'content_hash' => hash('sha256', 'x'), 'page_start' => 3, 'page_end' => 3]);
    WikiPage::factory()->live(['title' => '減免說明', 'body' => "免繳。[^1]\n\n[^1]: [注意事項](https://www.nou.edu.tw/114-2.pdf#page=6)\n"])->create(['slug' => 'fees']);
    WikiPage::factory()->live(['body' => "日期。[^1]\n\n[^1]: [注意事項](https://www.nou.edu.tw/114-2.pdf#page=3)\n"])->create(['slug' => 'dates']);

    $html = view('filament.wiki.rollover-preview', ['plan' => app(FindWikiRolloverPlan::class)($new)])->render();

    expect($html)
        ->toContain('減免說明')
        ->toContain('八、減免')
        ->toContain('part-8 → part-8')
        ->toContain('另有 1 個條目只引用沒有變動的段落')
        ->toContain('dates');
});

it('says so in the preview when no page relies on a changed section', function () use ($rolloverDocuments) {
    [$old, $new] = $rolloverDocuments();

    $html = view('filament.wiki.rollover-preview', ['plan' => app(FindWikiRolloverPlan::class)($new)])->render();

    expect($html)->toContain('沒有條目引用到有變動的段落');
});

it('shows a rollover request as one in the review screen', function () use ($rolloverDocuments) {
    [$old, $new] = $rolloverDocuments();
    $page = WikiPage::factory()->live()->create(['slug' => 'fees']);
    $editRequest = WikiEditRequest::factory()->forPage($page)->rollover($new)->create();

    Livewire::test(ViewWikiEditRequest::class, ['record' => $editRequest->uuid])
        ->assertSee('換學期更新')
        ->assertSee($new->title);
});
