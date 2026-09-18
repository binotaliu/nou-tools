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
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Date;
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

it('refreshes the calendar snapshot', function () {
    $issue = NewsletterIssue::factory()->publishingOn('2026-09-21')->create(['highlights_events' => []]);

    Livewire::test(EditNewsletterIssue::class, ['record' => $issue->getRouteKey()])
        ->callAction('refreshCalendar')
        ->assertNotified();

    expect(array_column($issue->refresh()->highlights_events, 'name'))->toBe(['期中考報名截止']);
});
