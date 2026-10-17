<?php

declare(strict_types=1);

use App\Enums\UserRole;
use App\Enums\WikiSourceDocumentStatus;
use App\Filament\Resources\WikiSourceDocuments\Pages\CreateWikiSourceDocument;
use App\Filament\Resources\WikiSourceDocuments\Pages\EditWikiSourceDocument;
use App\Filament\Resources\WikiSourceDocuments\Pages\ListWikiSourceDocuments;
use App\Filament\Resources\WikiSourceDocuments\RelationManagers\SectionsRelationManager;
use App\Jobs\FetchWikiSourceDocument;
use App\Models\User;
use App\Models\WikiSourceDocument;
use App\Models\WikiSourceSection;
use Filament\Actions\Testing\TestAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

uses(RefreshDatabase::class);

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
