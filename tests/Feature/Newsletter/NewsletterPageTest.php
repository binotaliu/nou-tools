<?php

use App\Enums\UserRole;
use App\Models\NewsletterColumn;
use App\Models\NewsletterIssue;
use App\Models\NewsletterItem;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;
use function Pest\Laravel\withoutVite;

beforeEach(function () {
    withoutVite();
    config(['newsletter.anchor_date' => '2026-09-21']);
});

function publishedIssueWithContent(string $publishesOn = '2026-09-21'): NewsletterIssue
{
    $issue = NewsletterIssue::factory()->publishingOn($publishesOn)->published()->create([
        'highlights_intro' => '這兩週要注意 **期中考報名**。<script>alert(1)</script>',
        'highlights_events' => [['start' => '2026-09-25', 'end' => '2026-09-25', 'name' => '期中考報名截止', 'description' => '逾期不受理']],
    ]);
    NewsletterItem::factory()->for($issue, 'issue')->create([
        'headline' => '期中考開始報名',
        'summary' => '請於 *期限內* 完成。',
        'source_name' => '教務處',
        'url' => 'https://studadm.nou.edu.tw/news/1',
    ]);
    NewsletterItem::factory()->for($issue, 'issue')->arts('學務處')->create(['headline' => '校園攝影展徵件']);
    NewsletterItem::factory()->for($issue, 'issue')->centers('臺北中心')->create(['headline' => '讀書會招募']);
    NewsletterColumn::factory()->for($issue, 'issue')->create([
        'title' => '浣熊站長的自言自語',
        'body' => '這是第一期。',
    ]);

    return $issue;
}

it('lists only published issues, newest first', function () {
    publishedIssueWithContent('2026-09-21');
    publishedIssueWithContent('2026-10-05');
    NewsletterIssue::factory()->publishingOn('2026-10-19')->ready()->create();

    get(route('newsletter.index'))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Newsletter/Index')
            ->where('viewModel.title', '浣熊的空大雙週報')
            ->where('viewModel.issues.data.0.issueKey', '2026-W41')
            ->where('viewModel.issues.data.1.issueKey', '2026-W39')
            ->where('viewModel.issues.data.1.title', '浣熊的空大雙週報 2026-W39')
            ->has('viewModel.issues.data', 2));
});

it('renders an issue with sections and server-rendered markdown', function () {
    publishedIssueWithContent('2026-09-21');
    publishedIssueWithContent('2026-10-05');

    get('/newsletter/2026-W39')
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Newsletter/Show')
            ->where('viewModel.issue.issueKey', '2026-W39')
            ->where('viewModel.issue.isPublished', true)
            ->where('viewModel.issue.highlightsIntro', fn (string $html) => str_contains($html, '<strong>期中考報名</strong>')
                && ! str_contains($html, '<script>'))
            ->where('viewModel.issue.highlightEvents.0.name', '期中考報名截止')
            ->where('viewModel.issue.highlightEvents.0.description', '逾期不受理')
            ->where('viewModel.issue.newsItems.0.headline', '期中考開始報名')
            ->where('viewModel.issue.newsItems.0.summary', fn (string $html) => str_contains($html, '<em>期限內</em>'))
            ->where('viewModel.issue.newsItems.0.url', 'https://studadm.nou.edu.tw/news/1')
            ->where('viewModel.issue.artItems.0.headline', '校園攝影展徵件')
            ->has('viewModel.issue.artItems', 1)
            ->where('viewModel.issue.centerItems.0.sourceName', '臺北中心')
            ->where('viewModel.issue.columns.0.title', '浣熊站長的自言自語')
            ->where('viewModel.previousIssue', null)
            ->where('viewModel.nextIssue.issueKey', '2026-W41'));
});

it('hides unpublished issues from guests but lets admins preview them', function () {
    NewsletterIssue::factory()->publishingOn('2026-09-21')->draft()->create();

    get('/newsletter/2026-W39')->assertNotFound();
    get('/newsletter/2026-W39.md')->assertNotFound();

    actingAs(User::factory()->create(['roles' => [UserRole::Admin->value]]));

    get('/newsletter/2026-W39')
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->where('viewModel.issue.isPublished', false)
            ->where('viewModel.issue.statusLabel', '草稿'));
});

it('does not let non-admin users preview drafts', function () {
    NewsletterIssue::factory()->publishingOn('2026-09-21')->draft()->create();

    actingAs(User::factory()->create());

    get('/newsletter/2026-W39')->assertNotFound();
});

it('returns 404 for unknown or malformed issue keys', function (string $path) {
    get($path)->assertNotFound();
})->with(['/newsletter/2026-W41', '/newsletter/2026-39', '/newsletter/latest']);

it('serves markdown twins', function () {
    publishedIssueWithContent();

    get('/newsletter.md')
        ->assertSuccessful()
        ->assertHeader('Content-Type', 'text/markdown; charset=utf-8')
        ->assertSee('浣熊的空大雙週報 2026-W39')
        ->assertSee(route('newsletter.feed'));

    get('/newsletter/2026-W39.md')
        ->assertSuccessful()
        ->assertHeader('Content-Type', 'text/markdown; charset=utf-8')
        ->assertSee('## 前言')
        ->assertSee('## 本期行事曆')
        ->assertSee('2026-09-25：期中考報名截止｜逾期不受理', false)
        ->assertSee('### 期中考開始報名')
        ->assertSee('請於 *期限內* 完成。', false)
        ->assertSee('## 藝文活動')
        ->assertSee('### 校園攝影展徵件')
        ->assertSee('### 臺北中心')
        ->assertSee('## 浣熊站長的自言自語');
});

it('serves an Atom feed of published issues', function () {
    publishedIssueWithContent();
    NewsletterIssue::factory()->publishingOn('2026-10-05')->draft()->create();

    $response = get(route('newsletter.feed'))
        ->assertSuccessful()
        ->assertHeader('Content-Type', 'application/atom+xml; charset=utf-8');

    $feed = simplexml_load_string($response->getContent());
    $feed->registerXPathNamespace('atom', 'http://www.w3.org/2005/Atom');
    $entries = $feed->xpath('//atom:entry');

    expect($entries)->toHaveCount(1)
        ->and((string) $entries[0]->title)->toBe('浣熊的空大雙週報 2026-W39')
        ->and((string) $entries[0]->content)->toContain('<h2>空大新消息</h2>')
        ->toContain('<h2>藝文活動</h2>')
        ->toContain('<strong>期中考報名</strong>')
        ->not->toContain('<script>');
});

it('lists the newsletter and published issues in the sitemap and llms.txt', function () {
    publishedIssueWithContent();
    NewsletterIssue::factory()->publishingOn('2026-10-05')->draft()->create();

    get(route('sitemap'))
        ->assertSuccessful()
        ->assertSee(route('newsletter.index'), false)
        ->assertSee(route('newsletter.show', '2026-W39'), false)
        ->assertDontSee(route('newsletter.show', '2026-W41'), false);

    get('/llms.txt')->assertSee(route('newsletter.feed'), false);
});
