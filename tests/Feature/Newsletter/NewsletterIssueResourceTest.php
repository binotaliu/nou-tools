<?php

use App\Enums\NewsletterIssueStatus;
use App\Enums\NewsletterSection;
use App\Enums\UserRole;
use App\Filament\Resources\NewsletterIssues\Pages\CreateNewsletterIssue;
use App\Filament\Resources\NewsletterIssues\Pages\EditNewsletterIssue;
use App\Filament\Resources\NewsletterIssues\Pages\ListNewsletterIssues;
use App\Jobs\DraftNewsletterIssueWithAi;
use App\Models\Announcement;
use App\Models\NewsletterIssue;
use App\Models\NewsletterItem;
use App\Models\User;
use Filament\Actions\Exceptions\ActionNotResolvableException;
use Filament\Actions\Testing\TestAction;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

beforeEach(function () {
    config(['newsletter.anchor_date' => '2026-09-21']);
    config(['school-schedules' => [
        '2026A' => [['start' => '2026-09-25', 'end' => '2026-09-25', 'name' => '期中考報名截止', 'countdown' => false]],
    ]]);

    actingAs(User::factory()->create(['roles' => [UserRole::Admin->value]]));
});

it('is admin only', function () {
    actingAs(User::factory()->create());

    get('/admin/newsletter-issues')->assertForbidden();
});

it('lists issues', function () {
    $issues = NewsletterIssue::factory()->count(2)->create();

    Livewire::test(ListNewsletterIssues::class)->assertCanSeeTableRecords($issues);
});

it('creates an issue for the next free cadence Monday', function () {
    $this->travelTo(Date::parse('2026-09-19 12:00', 'Asia/Taipei'));
    NewsletterIssue::factory()->publishingOn('2026-09-21')->create();

    Livewire::test(CreateNewsletterIssue::class)
        ->assertSet('data.publishes_on', '2026-10-05')
        ->fillForm(['title' => '第二期'])
        ->call('create')
        ->assertHasNoFormErrors();

    $issue = NewsletterIssue::query()->where('issue_key', '2026-W41')->sole();

    expect($issue->title)->toBe('第二期')
        ->and($issue->covers_from->toDateString())->toBe('2026-09-21')
        ->and($issue->status)->toBe(NewsletterIssueStatus::Draft);
});

it('rejects an off-cadence publish date', function () {
    Livewire::test(CreateNewsletterIssue::class)
        ->fillForm(['publishes_on' => '2026-09-28'])
        ->call('create')
        ->assertHasFormErrors(['publishes_on']);

    expect(NewsletterIssue::count())->toBe(0);
});

it('saves an optional short description on calendar events', function () {
    $issue = NewsletterIssue::factory()->publishingOn('2026-09-21')->create(['highlights_events' => []]);

    Livewire::test(EditNewsletterIssue::class, ['record' => $issue->getRouteKey()])
        ->fillForm([
            'highlights_events' => [
                ['start' => '2026-09-25', 'end' => '2026-09-25', 'name' => '期中考報名截止', 'description' => '逾期不受理'],
                ['start' => '2026-09-28', 'end' => '2026-09-28', 'name' => '教師節', 'description' => null],
            ],
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    expect(array_column($issue->refresh()->highlights_events, 'description', 'name'))
        ->toBe(['期中考報名截止' => '逾期不受理', '教師節' => null]);
});

it('edits intro, items and columns', function () {
    $issue = NewsletterIssue::factory()->publishingOn('2026-09-21')->create();
    $announcement = Announcement::factory()->create(['source_name' => '臺北中心', 'title' => '讀書會招募']);

    Livewire::test(EditNewsletterIssue::class, ['record' => $issue->getRouteKey()])
        ->fillForm([
            'highlights_intro' => '新的開場',
            'items' => [
                [
                    'section' => NewsletterSection::Centers->value,
                    'announcement_id' => $announcement->id,
                    'source_name' => '臺北中心',
                    'url' => 'https://www.nou.edu.tw/a',
                    'headline' => '讀書會招募中',
                    'summary' => '歡迎參加',
                ],
            ],
            'columns' => [
                ['title' => '浣熊站長的自言自語', 'author' => '浣熊站長', 'body' => '第一期！'],
            ],
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    $issue->refresh();

    expect($issue->highlights_intro)->toBe('新的開場')
        ->and($issue->centerItems()->sole()->only(['announcement_id', 'headline']))->toBe(['announcement_id' => $announcement->id, 'headline' => '讀書會招募中'])
        ->and($issue->columns()->sole()->body)->toBe('第一期！');
});

it('saves a cover image', function () {
    Storage::fake(NewsletterIssue::COVER_DISK);
    $issue = NewsletterIssue::factory()->publishingOn('2026-09-21')->create();
    $file = UploadedFile::fake()->image('cover.jpg');

    Livewire::test(EditNewsletterIssue::class, ['record' => $issue->getRouteKey()])
        ->fillForm(['cover_image' => $file])
        ->call('save')
        ->assertHasNoFormErrors();

    $issue->refresh();

    expect($issue->cover_image)->not->toBeNull();
    Storage::disk(NewsletterIssue::COVER_DISK)->assertExists($issue->cover_image);
});

it('saves the cover image credit alongside the image', function () {
    Storage::fake(NewsletterIssue::COVER_DISK);
    $issue = NewsletterIssue::factory()->publishingOn('2026-09-21')->create();
    $file = UploadedFile::fake()->image('cover.jpg');

    // The Unsplash picker's afterUpload hook stashes the credit in the
    // session and cover_image's own afterStateUpdated consumes it into
    // these two fields (see NewsletterIssueForm); simulate the end state
    // here since driving the picker's own nested Livewire action isn't
    // practical in a form test.
    Livewire::test(EditNewsletterIssue::class, ['record' => $issue->getRouteKey()])
        ->fillForm([
            'cover_image' => $file,
            'cover_image_credit_name' => 'Nathan Dumlao',
            'cover_image_credit_url' => 'https://unsplash.com/@nate_dumlao',
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    $issue->refresh();

    expect($issue->cover_image_credit_name)->toBe('Nathan Dumlao')
        ->and($issue->cover_image_credit_url)->toBe('https://unsplash.com/@nate_dumlao');
});

it('clears the cover image credit when the image is removed', function () {
    Storage::fake(NewsletterIssue::COVER_DISK);
    $issue = NewsletterIssue::factory()->publishingOn('2026-09-21')->create([
        'cover_image' => 'old.jpg',
        'cover_image_credit_name' => 'Nathan Dumlao',
        'cover_image_credit_url' => 'https://unsplash.com/@nate_dumlao',
    ]);

    Livewire::test(EditNewsletterIssue::class, ['record' => $issue->getRouteKey()])
        ->fillForm(['cover_image' => null])
        ->call('save')
        ->assertHasNoFormErrors();

    $issue->refresh();

    expect($issue->cover_image_credit_name)->toBeNull()
        ->and($issue->cover_image_credit_url)->toBeNull();
});

it('fills source, url and headline when an announcement is picked', function () {
    $issue = NewsletterIssue::factory()->publishingOn('2026-09-21')->create();
    $announcement = Announcement::factory()->create(['source_name' => '教務處', 'title' => '加退選公告', 'url' => 'https://studadm.nou.edu.tw/x']);

    $component = Livewire::test(EditNewsletterIssue::class, ['record' => $issue->getRouteKey()])
        ->fillForm(['items' => [['section' => 'news', 'announcement_id' => null, 'source_name' => '', 'url' => '', 'headline' => '', 'summary' => '']]]);

    $itemKey = array_key_first($component->get('data.items'));

    $component->set("data.items.{$itemKey}.announcement_id", $announcement->id)
        ->assertSet("data.items.{$itemKey}.source_name", '教務處')
        ->assertSet("data.items.{$itemKey}.url", 'https://studadm.nou.edu.tw/x')
        ->assertSet("data.items.{$itemKey}.headline", '加退選公告');
});

it('moves an issue between draft and ready, and publishes it', function () {
    $issue = NewsletterIssue::factory()->publishingOn('2026-09-21')->create();

    Livewire::test(EditNewsletterIssue::class, ['record' => $issue->getRouteKey()])
        ->assertActionHidden('returnToDraft')
        ->callAction('markReady')
        ->assertNotified();

    expect($issue->refresh()->status)->toBe(NewsletterIssueStatus::Ready);

    Livewire::test(EditNewsletterIssue::class, ['record' => $issue->getRouteKey()])
        ->assertActionHidden('markReady')
        ->callAction('returnToDraft');

    expect($issue->refresh()->status)->toBe(NewsletterIssueStatus::Draft);

    Livewire::test(EditNewsletterIssue::class, ['record' => $issue->getRouteKey()])
        ->callAction('publishNow')
        ->assertNotified();

    expect($issue->refresh()->status)->toBe(NewsletterIssueStatus::Published);

    Livewire::test(EditNewsletterIssue::class, ['record' => $issue->getRouteKey()])
        ->assertActionHidden('publishNow')
        ->assertActionHidden('aiDraft');
});

it('reports an empty issue it cannot publish', function () {
    $issue = NewsletterIssue::factory()->publishingOn('2026-09-21')->create(['highlights_intro' => null]);

    Livewire::test(EditNewsletterIssue::class, ['record' => $issue->getRouteKey()])
        ->callAction('publishNow')
        ->assertNotified();

    expect($issue->refresh()->status)->toBe(NewsletterIssueStatus::Draft);
});

it('dispatches the AI draft after the response', function () {
    Bus::fake();
    $issue = NewsletterIssue::factory()->publishingOn('2026-09-21')->create();
    NewsletterItem::factory()->for($issue, 'issue')->create();

    Livewire::test(EditNewsletterIssue::class, ['record' => $issue->getRouteKey()])
        ->callAction('aiDraft')
        ->assertNotified();

    Bus::assertDispatchedAfterResponse(DraftNewsletterIssueWithAi::class, fn (DraftNewsletterIssueWithAi $job): bool => $job->issue->is($issue));
});

it('imports calendar events into the form without saving', function () {
    $issue = NewsletterIssue::factory()->publishingOn('2026-09-21')->create(['highlights_events' => []]);

    $component = Livewire::test(EditNewsletterIssue::class, ['record' => $issue->getRouteKey()])
        ->callAction(TestAction::make('importCalendarEvents')->schemaComponent('highlightsSection'))
        ->assertNotified();

    expect($issue->refresh()->highlights_events)->toBe([]);

    $component->call('save');

    expect(array_column($issue->refresh()->highlights_events, 'name'))->toBe(['期中考報名截止']);
});

it('imports the announcements picked in the modal as items', function () {
    $issue = NewsletterIssue::factory()->publishingOn('2026-09-21')->create();
    $inWindow = Announcement::factory()->create([
        'source_name' => '教務處',
        'title' => '加退選公告',
        'url' => 'https://studadm.nou.edu.tw/x',
        'published_at' => $issue->covers_from->toDateString().' 10:00:00',
    ]);
    $alreadyImported = Announcement::factory()->create(['published_at' => $issue->covers_from->toDateString().' 11:00:00']);
    NewsletterItem::factory()->for($issue, 'issue')->create(['announcement_id' => $alreadyImported->id]);

    Livewire::test(EditNewsletterIssue::class, ['record' => $issue->getRouteKey()])
        ->callAction(TestAction::make('importAnnouncements')->schemaComponent('itemsSection'), ['news' => [$inWindow->id]])
        ->assertNotified()
        ->call('save');

    expect($issue->items()->pluck('announcement_id')->all())->toContain($inWindow->id, $alreadyImported->id)
        ->and($issue->items()->where('announcement_id', $inWindow->id)->sole()->only(['section', 'source_name', 'headline']))
        ->toBe(['section' => NewsletterSection::News, 'source_name' => '教務處', 'headline' => '加退選公告']);
});

it('imports an announcement ticked under both news and arts only once, as news', function () {
    $issue = NewsletterIssue::factory()->publishingOn('2026-09-21')->create();
    $announcement = Announcement::factory()->create([
        'source_name' => '學務處',
        'title' => '校園攝影展徵件',
        'published_at' => $issue->covers_from->toDateString().' 10:00:00',
    ]);
    $artsOnly = Announcement::factory()->create([
        'source_name' => '學務處',
        'title' => '弦樂音樂會',
        'published_at' => $issue->covers_from->toDateString().' 11:00:00',
    ]);

    Livewire::test(EditNewsletterIssue::class, ['record' => $issue->getRouteKey()])
        ->callAction(TestAction::make('importAnnouncements')->schemaComponent('itemsSection'), [
            'news' => [$announcement->id],
            'arts' => [$announcement->id, $artsOnly->id],
        ])
        ->assertNotified()
        ->call('save');

    expect($issue->items()->count())->toBe(2)
        ->and($issue->newsItems()->pluck('announcement_id')->all())->toBe([$announcement->id])
        ->and($issue->artItems()->pluck('announcement_id')->all())->toBe([$artsOnly->id]);
});

it('hides the import actions on a published issue', function () {
    $issue = NewsletterIssue::factory()->publishingOn('2026-09-21')->create(['status' => NewsletterIssueStatus::Published]);

    foreach (['importCalendarEvents' => 'highlightsSection', 'importAnnouncements' => 'itemsSection'] as $action => $section) {
        expect(fn () => Livewire::test(EditNewsletterIssue::class, ['record' => $issue->getRouteKey()])
            ->callAction(TestAction::make($action)->schemaComponent($section)))
            ->toThrow(ActionNotResolvableException::class);
    }
});
