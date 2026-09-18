<?php

use App\Enums\NewsletterIssueStatus;
use App\Enums\NewsletterSection;
use App\Models\Announcement;
use App\Models\NewsletterIssue;
use App\Models\NewsletterItem;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Log;
use NouTools\Domains\Newsletter\Actions\ChangeNewsletterIssueReadiness;
use NouTools\Domains\Newsletter\Actions\CreateNewsletterDraft;
use NouTools\Domains\Newsletter\Actions\ListNewsletterCandidateAnnouncements;
use NouTools\Domains\Newsletter\Actions\PublishDueNewsletterIssues;
use NouTools\Domains\Newsletter\Actions\PublishNewsletterIssue;
use NouTools\Domains\Newsletter\Schedule\NewsletterCadence;

beforeEach(function () {
    config(['newsletter.anchor_date' => '2026-09-21']);
    config(['school-schedules' => [
        '2026A' => [
            ['start' => '2026-09-25', 'end' => '2026-09-25', 'name' => '期中考報名截止', 'countdown' => false],
            ['start' => '2026-11-01', 'end' => '2026-11-01', 'name' => '期中考', 'countdown' => true],
        ],
    ]]);
});

it('creates a draft with windows and a calendar snapshot, idempotently', function () {
    $schedule = app(NewsletterCadence::class)->forPublishDate('2026-09-21');

    $issue = app(CreateNewsletterDraft::class)($schedule);
    $again = app(CreateNewsletterDraft::class)($schedule);

    expect($again->is($issue))->toBeTrue()
        ->and(NewsletterIssue::count())->toBe(1)
        ->and($issue->issue_key)->toBe('2026-W39')
        ->and($issue->status)->toBe(NewsletterIssueStatus::Draft)
        ->and($issue->covers_from->toDateString())->toBe('2026-09-07')
        ->and($issue->highlights_to->toDateString())->toBe('2026-10-04')
        ->and($issue->highlights_events)->toBe([
            ['start' => '2026-09-25', 'end' => '2026-09-25', 'name' => '期中考報名截止'],
        ]);
});

it('splits candidate announcements into sections within the window', function () {
    config(['announcements.source_groups' => [
        '教務處' => 'administrative',
        '臺北中心' => 'center',
        '管理與資訊學系' => 'department',
    ]]);

    $news = Announcement::factory()->create(['source_name' => '教務處', 'published_at' => '2026-09-10 00:00:00']);
    $department = Announcement::factory()->create(['source_name' => '管理與資訊學系', 'published_at' => '2026-09-20 00:00:00']);
    $center = Announcement::factory()->create(['source_name' => '臺北中心', 'published_at' => '2026-09-07 00:00:00']);
    $unlisted = Announcement::factory()->create(['source_name' => '未列來源', 'published_at' => null, 'fetched_at' => '2026-09-12 03:00:00']);
    Announcement::factory()->create(['source_name' => '教務處', 'published_at' => '2026-09-21 00:00:00']);
    Announcement::factory()->create(['source_name' => '臺北中心', 'published_at' => '2026-09-06 23:00:00']);

    $candidates = app(ListNewsletterCandidateAnnouncements::class)(
        Date::parse('2026-09-07'),
        Date::parse('2026-09-20'),
    );

    expect($candidates[NewsletterSection::News->value]->modelKeys())->toEqualCanonicalizing([$news->id, $department->id, $unlisted->id])
        ->and($candidates[NewsletterSection::Centers->value]->modelKeys())->toBe([$center->id]);
});

it('refuses to publish an empty issue', function () {
    $issue = NewsletterIssue::factory()->create(['highlights_intro' => null]);

    app(PublishNewsletterIssue::class)($issue);
})->throws(DomainException::class);

it('toggles readiness but not once published', function () {
    $issue = NewsletterIssue::factory()->create();

    app(ChangeNewsletterIssueReadiness::class)($issue, true);
    expect($issue->fresh()->status)->toBe(NewsletterIssueStatus::Ready);

    app(ChangeNewsletterIssueReadiness::class)($issue, false);
    expect($issue->fresh()->status)->toBe(NewsletterIssueStatus::Draft);

    $published = NewsletterIssue::factory()->published()->create();
    expect(fn () => app(ChangeNewsletterIssueReadiness::class)($published, false))->toThrow(DomainException::class);
});

it('publishes due ready issues and leaves drafts and future issues alone', function () {
    Log::spy();

    $ready = NewsletterIssue::factory()->publishingOn('2026-09-21')->ready()->create();
    $draft = NewsletterIssue::factory()->publishingOn('2026-10-05')->draft()->create();
    NewsletterItem::factory()->for($draft, 'issue')->create();
    $future = NewsletterIssue::factory()->publishingOn('2026-10-19')->ready()->create();

    $this->travelTo(Date::parse('2026-10-05 08:00', 'Asia/Taipei'));

    $result = app(PublishDueNewsletterIssues::class)(Date::parse('2026-10-05'));

    expect($result['published']->pluck('id')->all())->toBe([$ready->id])
        ->and($ready->fresh()->status)->toBe(NewsletterIssueStatus::Published)
        ->and($ready->fresh()->published_at)->not->toBeNull()
        ->and($draft->fresh()->status)->toBe(NewsletterIssueStatus::Draft)
        ->and($future->fresh()->status)->toBe(NewsletterIssueStatus::Ready);

    Log::shouldHaveReceived('warning')
        ->withArgs(fn (string $message, array $context): bool => $context['issue_key'] === '2026-W41')
        ->once();
});
