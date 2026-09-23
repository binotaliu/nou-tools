<?php

use App\Enums\UserRole;
use App\Models\NewsletterColumn;
use App\Models\NewsletterIssue;
use App\Models\NewsletterItem;
use App\Models\User;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;
use function Pest\Laravel\withoutVite;

beforeEach(function () {
    withoutVite();
    config(['newsletter.anchor_date' => '2026-09-21']);
});

$publishedIssueWithContent = function (string $publishesOn = '2026-09-21'): NewsletterIssue {
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
};

it('highlights the latest published issue and lists the rest as past issues', function () use ($publishedIssueWithContent) {
    $publishedIssueWithContent('2026-09-21');
    $publishedIssueWithContent('2026-10-05');
    NewsletterIssue::factory()->publishingOn('2026-10-19')->ready()->create();

    get(route('newsletter.index'))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Newsletter/Index')
            ->where('viewModel.title', '浣熊的空大雙週報')
            ->where('viewModel.latestIssue.issueKey', '2026-W41')
            ->where('viewModel.issues.data.0.issueKey', '2026-W39')
            ->where('viewModel.issues.data.0.title', '浣熊的空大雙週報 2026-W39')
            ->has('viewModel.issues.data', 1));
});

it('exposes cover image URLs for the highlight and past issues', function () {
    NewsletterIssue::factory()->publishingOn('2026-09-21')->published()->create(['cover_image' => 'newsletter/old.jpg']);
    NewsletterIssue::factory()->publishingOn('2026-10-05')->published()->create(['cover_image' => 'newsletter/new.jpg']);

    get(route('newsletter.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('viewModel.latestIssue.coverImageUrl', Storage::disk(NewsletterIssue::COVER_DISK)->url('newsletter/new.jpg'))
            ->where('viewModel.issues.data.0.coverImageUrl', Storage::disk(NewsletterIssue::COVER_DISK)->url('newsletter/old.jpg')));
});

it('serves covers from the S3 disk URL and allows its origin in the CSP', function () {
    config([
        'filesystems.disks.'.NewsletterIssue::COVER_DISK.'.disk' => 's3',
        'filesystems.disks.s3' => [
            'driver' => 's3',
            'bucket' => 'covers',
            'region' => 'ap-northeast-1',
            'key' => 'key',
            'secret' => 'secret',
            'url' => 'https://d111111abcdef8.cloudfront.net',
        ],
    ]);
    NewsletterIssue::factory()->publishingOn('2026-09-21')->published()->create(['cover_image' => 'new.jpg']);

    $response = get(route('newsletter.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('viewModel.latestIssue.coverImageUrl', 'https://d111111abcdef8.cloudfront.net/newsletter-covers/new.jpg'));

    expect(NewsletterIssue::coverImageOrigin())->toBe('https://d111111abcdef8.cloudfront.net')
        ->and((string) $response->headers->get('Content-Security-Policy'))
        ->toContain('https://d111111abcdef8.cloudfront.net');
});

it('serves covers locally when no remote bucket is configured', function () {
    expect(NewsletterIssue::coverImageOrigin())->toBeNull();
});

it('lets the admin panel fetch Filament\'s presigned cover previews straight from the S3 bucket', function () {
    config([
        'filesystems.disks.'.NewsletterIssue::COVER_DISK.'.disk' => 's3',
        'filesystems.disks.s3' => [
            'driver' => 's3',
            'bucket' => 'covers',
            'region' => 'ap-northeast-1',
            'key' => 'key',
            'secret' => 'secret',
            'url' => 'https://d111111abcdef8.cloudfront.net',
        ],
    ]);
    /** @var User&Authenticatable $user */
    $user = User::factory()->createOne();

    $connectSrc = collect(explode(';', (string) actingAs($user)->get(route('filament.admin.auth.profile'))->headers->get('Content-Security-Policy')))
        ->first(fn (string $directive) => str_starts_with(trim($directive), 'connect-src'));

    expect(NewsletterIssue::coverBucketOrigin())->toBe('https://covers.s3.ap-northeast-1.amazonaws.com')
        ->and($connectSrc)->toContain('https://covers.s3.ap-northeast-1.amazonaws.com');
});

it('has no bucket origin while covers are stored locally', function () {
    config(['filesystems.disks.'.NewsletterIssue::COVER_DISK.'.disk' => 'public']);

    expect(NewsletterIssue::coverBucketOrigin())->toBeNull();
});

it('has no highlight or past issues before the first issue is published', function () {
    NewsletterIssue::factory()->publishingOn('2026-10-19')->ready()->create();

    get(route('newsletter.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('viewModel.latestIssue', null)
            ->has('viewModel.issues.data', 0));
});

it('renders an issue with sections and server-rendered markdown', function () use ($publishedIssueWithContent) {
    $publishedIssueWithContent('2026-09-21');
    $publishedIssueWithContent('2026-10-05');

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

it('serves markdown twins', function () use ($publishedIssueWithContent) {
    $publishedIssueWithContent();

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

it('serves an Atom feed of published issues', function () use ($publishedIssueWithContent) {
    $publishedIssueWithContent();
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

it('lists the newsletter and published issues in the sitemap and llms.txt', function () use ($publishedIssueWithContent) {
    $publishedIssueWithContent();
    NewsletterIssue::factory()->publishingOn('2026-10-05')->draft()->create();

    get(route('sitemap'))
        ->assertSuccessful()
        ->assertSee(route('newsletter.index'), false)
        ->assertSee(route('newsletter.show', '2026-W39'), false)
        ->assertDontSee(route('newsletter.show', '2026-W41'), false);

    get('/llms.txt')->assertSee(route('newsletter.feed'), false);
});

it('orders center items like the directory: by region, then configured order', function () {
    $issue = NewsletterIssue::factory()->publishingOn('2026-09-21')->published()->create();

    foreach (['金門中心', '高雄中心', '台中中心', '基隆中心', '花蓮中心', '桃園中心'] as $center) {
        NewsletterItem::factory()->for($issue, 'issue')->centers($center)->create();
    }

    get(route('newsletter.show', $issue->issue_key))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->where('viewModel.issue.centerItems', fn ($items) => collect($items)->pluck('sourceName')->all() === [
                '基隆中心', '桃園中心', '台中中心', '高雄中心', '花蓮中心', '金門中心',
            ]));
});

it('advertises a generated og:image card for published issues', function () use ($publishedIssueWithContent) {
    $publishedIssueWithContent();

    get('/newsletter/2026-W39')
        ->assertSuccessful()
        ->assertSee('<template data-og-image', false)
        ->assertSee('浣熊的空大雙週報 2026-W39', false)
        ->assertSee('<span>NOU 小幫手</span>', false)
        ->assertSee('<meta property="og:image" content="'.url('/og-image/'), false)
        ->assertDontSee(asset('og-image.png'), false);
});

it('advertises Open Graph and Twitter tags for published issues', function () use ($publishedIssueWithContent) {
    $publishedIssueWithContent();

    get('/newsletter/2026-W39')
        ->assertSuccessful()
        ->assertSee('<meta data-seo property="og:type" content="article" />', false)
        ->assertSee('<meta data-seo property="og:site_name" content="NOU 小幫手" />', false)
        ->assertSee('<meta data-seo property="og:title" content="浣熊的空大雙週報 2026-W39" />', false)
        ->assertSee('<meta data-seo property="og:url" content="'.url('/newsletter/2026-W39').'" />', false)
        ->assertSee('<meta data-seo property="og:description" content="這兩週要注意 期中考報名。alert(1)" />', false)
        ->assertSee('<meta data-seo name="description" content="這兩週要注意 期中考報名。alert(1)" />', false)
        ->assertSee('property="article:published_time"', false)
        ->assertSee('<meta data-seo name="twitter:card" content="summary_large_image" />', false);
});

it('falls back to a generic description when the issue has no intro', function () {
    NewsletterIssue::factory()->publishingOn('2026-09-21')->published()->create(['highlights_intro' => null]);

    get('/newsletter/2026-W39')
        ->assertSuccessful()
        ->assertSee('<meta data-seo property="og:description" content="浣熊的空大雙週報 2026-W39：', false);
});

it('does not emit issue Open Graph tags on the index', function () {
    get(route('newsletter.index'))
        ->assertSuccessful()
        ->assertSee('<meta data-seo property="og:type" content="website" />', false)
        ->assertDontSee('property="article:published_time"', false);
});

it('keeps the static og:image on pages without a card', function () {
    get(route('newsletter.index'))
        ->assertSuccessful()
        ->assertDontSee('<template data-og-image', false)
        ->assertSee('<meta property="og:image" content="'.asset('og-image.png').'"', false);
});

it('renders the card for admins previewing drafts', function () {
    NewsletterIssue::factory()->publishingOn('2026-09-21')->draft()->create();

    actingAs(User::factory()->create(['roles' => [UserRole::Admin->value]]));

    get('/newsletter/2026-W39')
        ->assertSuccessful()
        ->assertSee('<template data-og-image', false);
});

it('allows Google Fonts in the CSP only when rendering the og-image card', function () use ($publishedIssueWithContent) {
    $publishedIssueWithContent();

    $policy = fn ($response): string => (string) $response->headers->get('Content-Security-Policy');

    expect($policy(get('/newsletter/2026-W39?ogimage')))
        ->toContain('https://fonts.googleapis.com')
        ->toContain('https://fonts.gstatic.com')
        ->and($policy(get('/newsletter/2026-W39')))
        ->not->toContain('fonts.googleapis.com')
        ->not->toContain('fonts.gstatic.com');
});

it('loads Noto Sans TC in the og-image screenshot document', function () use ($publishedIssueWithContent) {
    $publishedIssueWithContent();

    get('/newsletter/2026-W39?ogimage')
        ->assertSuccessful()
        ->assertSee('family=Noto+Sans+TC', false)
        ->assertSee("font-family: 'Noto Sans TC'", false);
});
