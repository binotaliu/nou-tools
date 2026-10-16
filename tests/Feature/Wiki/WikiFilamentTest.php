<?php

declare(strict_types=1);

use App\Enums\UserRole;
use App\Enums\WikiEditRequestStatus;
use App\Enums\WikiRevisionOrigin;
use App\Filament\Resources\WikiEditRequests\Pages\ListWikiEditRequests;
use App\Filament\Resources\WikiEditRequests\Pages\ViewWikiEditRequest;
use App\Filament\Resources\WikiPages\Pages\CreateWikiPage;
use App\Filament\Resources\WikiPages\Pages\EditWikiPage;
use App\Filament\Resources\WikiPages\Pages\ListWikiPages;
use App\Filament\Resources\WikiPages\RelationManagers\RevisionsRelationManager;
use App\Models\User;
use App\Models\WikiEditRequest;
use App\Models\WikiPage;
use App\Models\WikiRevision;
use Filament\Actions\Testing\TestAction;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

beforeEach(function (): void {
    $this->admin = User::factory()->create(['roles' => [UserRole::Admin->value]]);
    actingAs($this->admin);

    $this->page = WikiPage::factory()->live([
        'title' => '空大考試要去哪裡考？',
        'body' => "期中考在週末。\n",
        'categories' => ['考試'],
    ])->create(['slug' => 'nou-exams-info']);
});

it('is admin only', function (): void {
    actingAs(User::factory()->create());

    get('/admin/wiki-pages')->assertForbidden();
    get('/admin/wiki-edit-requests')->assertForbidden();
});

it('lists pages', function (): void {
    Livewire::test(ListWikiPages::class)->assertCanSeeTableRecords([$this->page]);
});

it('creates a page as an admin revision', function (): void {
    Livewire::test(CreateWikiPage::class)
        ->fillForm([
            'title' => '學分抵免',
            'slug' => 'credit-transfer',
            'description' => '已修過的學分可以申請抵免。',
            'categories' => ['選課'],
            'body' => "已修過的學分可以申請抵免。\n",
            'is_protected' => true,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $page = WikiPage::query()->where('slug', 'credit-transfer')->firstOrFail();

    expect($page->is_protected)->toBeTrue()
        ->and($page->currentRevision->origin)->toBe(WikiRevisionOrigin::Admin)
        ->and($page->currentRevision->user_id)->toBe($this->admin->id)
        ->and($page->categories->pluck('name')->all())->toBe(['選課']);
});

it('rejects an invalid or reserved slug', function (string $slug): void {
    Livewire::test(CreateWikiPage::class)
        ->fillForm(['title' => '標題', 'slug' => $slug, 'categories' => ['選課'], 'body' => '內容'])
        ->call('create')
        ->assertHasFormErrors(['slug']);
})->with(['Credit Transfer', 'new', 'nou-exams-info']);

it('edits a page into a new revision and renames with a redirect', function (): void {
    Livewire::test(EditWikiPage::class, ['record' => $this->page->slug])
        ->assertSchemaStateSet(['title' => '空大考試要去哪裡考？', 'body' => "期中考在週末。\n"])
        ->fillForm(['body' => "期中考在週末，期末考也是。\n", 'slug' => 'exams', 'edit_summary' => '補充期末考'])
        ->call('save')
        ->assertHasNoFormErrors();

    $page = $this->page->fresh();

    expect($page->slug)->toBe('exams')
        ->and($page->currentRevision->edit_summary)->toBe('補充期末考')
        ->and($page->revisions()->count())->toBe(2)
        ->and($page->slugRedirects()->pluck('old_slug')->all())->toBe(['nou-exams-info']);
});

it('edits the homepage intro without categories', function (): void {
    Livewire::test(EditWikiPage::class, ['record' => WikiPage::MAIN_PAGE_SLUG])
        ->fillForm(['body' => "歡迎！\n", 'categories' => []])
        ->call('save')
        ->assertHasNoFormErrors();

    expect(WikiPage::query()->where('slug', WikiPage::MAIN_PAGE_SLUG)->first()->currentRevision->body)->toBe("歡迎！\n");

    Livewire::test(EditWikiPage::class, ['record' => $this->page->slug])
        ->fillForm(['categories' => []])
        ->call('save')
        ->assertHasFormErrors(['categories' => 'required']);
});

it('toggles protection and 精選 without a new revision', function (): void {
    Livewire::test(EditWikiPage::class, ['record' => $this->page->slug])
        ->fillForm(['is_protected' => true, 'is_featured' => true])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($this->page->fresh()->is_protected)->toBeTrue()
        ->and($this->page->fresh()->is_featured)->toBeTrue()
        ->and($this->page->revisions()->count())->toBe(1);
});

it('reverts to an older revision', function (): void {
    $first = WikiRevision::query()->findOrFail($this->page->current_revision_id);
    $second = WikiRevision::factory()->for($this->page, 'page')->create(['body' => '新版本。', 'parent_revision_id' => $first->id]);
    $this->page->current_revision_id = $second->id;
    $this->page->save();

    Livewire::test(RevisionsRelationManager::class, ['ownerRecord' => $this->page->fresh(), 'pageClass' => EditWikiPage::class])
        ->callAction(TestAction::make('revert')->table($first));

    $current = $this->page->fresh()->currentRevision;

    expect($current->origin)->toBe(WikiRevisionOrigin::Revert)
        ->and($current->body)->toBe("期中考在週末。\n");
});

it('lists pending requests by default', function (): void {
    $pending = WikiEditRequest::factory()->forPage($this->page)->awaitingConfirmation()->create(['status' => WikiEditRequestStatus::PendingReview]);
    $drafting = WikiEditRequest::factory()->forPage($this->page)->create();

    Livewire::test(ListWikiEditRequests::class)
        ->assertCanSeeTableRecords([$pending])
        ->assertCanNotSeeTableRecords([$drafting]);
});

it('approves a pending request', function (): void {
    $editRequest = WikiEditRequest::factory()->forPage($this->page)->awaitingConfirmation()->create(['status' => WikiEditRequestStatus::PendingReview]);

    Livewire::test(ViewWikiEditRequest::class, ['record' => $editRequest->uuid])
        ->assertOk()
        ->callAction('approve')
        ->assertNotified('已發布');

    expect($editRequest->refresh()->status)->toBe(WikiEditRequestStatus::Published)
        ->and($this->page->fresh()->current_revision_id)->toBe($editRequest->published_revision_id);
});

it('approves a new page under the slug the admin chose', function (): void {
    $editRequest = WikiEditRequest::factory()->awaitingConfirmation(['draft_slug' => 'credit-transfer', 'draft_categories' => ['選課']])
        ->create(['status' => WikiEditRequestStatus::PendingReview]);

    Livewire::test(ViewWikiEditRequest::class, ['record' => $editRequest->uuid])
        ->callAction('approve', ['slug' => 'credit-transfer-rules']);

    expect(WikiPage::query()->where('slug', 'credit-transfer-rules')->exists())->toBeTrue();
});

it('rejects with a note and blocks the device', function (): void {
    $editRequest = WikiEditRequest::factory()->forPage($this->page)->awaitingConfirmation()->create(['status' => WikiEditRequestStatus::PendingReview]);

    Livewire::test(ViewWikiEditRequest::class, ['record' => $editRequest->uuid])
        ->callAction('reject', ['note' => '來源不足'])
        ->callAction('block');

    expect($editRequest->refresh()->status)->toBe(WikiEditRequestStatus::Rejected)
        ->and($editRequest->review_note)->toBe('來源不足')
        ->and($editRequest->studentSchedule->wiki_blocked_at)->not->toBeNull();
});
