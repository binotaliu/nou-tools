<?php

declare(strict_types=1);

use App\Enums\WikiEditRequestStatus;
use App\Enums\WikiRevisionOrigin;
use App\Jobs\GenerateWikiEditDraft;
use App\Models\ScheduleDevice;
use App\Models\StudentSchedule;
use App\Models\WikiCategory;
use App\Models\WikiEditRequest;
use App\Models\WikiPage;
use App\Models\WikiSlugRedirect;
use Coderflex\LaravelTurnstile\Facades\LaravelTurnstile;
use Illuminate\Support\Facades\Bus;
use Inertia\Testing\AssertableInertia as Assert;
use NouTools\Domains\Schedules\Actions\BuildScheduleDeviceCookie;
use NouTools\Domains\Wiki\Actions\PublishWikiRevision;
use NouTools\Domains\Wiki\DataTransferObjects\PublishWikiRevisionDTO;

use function Pest\Laravel\withoutVite;

beforeEach(function (): void {
    withoutVite();

    $this->page = WikiPage::factory()->live([
        'title' => '空大考試要去哪裡考？',
        'description' => '期中考與期末考都在週末舉行。',
        'body' => "期中考與期末考都在週末舉行。\n\n## 考場\n\n各學習指導中心。詳見[[credit-transfer|學分抵免]]。\n",
        'categories' => ['考試'],
    ])->create(['slug' => 'nou-exams-info']);

    $category = new WikiCategory;
    $category->name = '考試';
    $category->save();
    $category->pages()->attach($this->page);

    $this->schedule = StudentSchedule::factory()->create();
    ScheduleDevice::factory()->for($this->schedule)->withToken('wiki-device')->create();

    $this->signedIn = fn () => $this->withCookie(BuildScheduleDeviceCookie::NAME, 'wiki-device');

    $this->passTurnstile = fn () => LaravelTurnstile::shouldReceive('validate')->andReturn(['success' => true]);
});

it('shows the wiki index with pages, categories and recent changes', function (): void {
    $this->get(route('wiki.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Wiki/Index')
            ->where('viewModel.pages.0.slug', 'nou-exams-info')
            ->where('viewModel.categories.0.name', '考試')
            ->where('viewModel.categories.0.pageCount', 1)
            ->where('viewModel.recentChanges.0.pageSlug', 'nou-exams-info')
            ->where('viewModel.searchResults', null));
});

it('searches pages', function (): void {
    $this->get(route('wiki.index', ['q' => '考場']))
        ->assertInertia(fn (Assert $page) => $page
            ->where('viewModel.query', '考場')
            ->where('viewModel.searchResults.0.slug', 'nou-exams-info'));
});

it('shows a page with rendered content and backlinks', function (): void {
    $linking = WikiPage::factory()->live(['title' => '新生入門', 'body' => '先看[[nou-exams-info]]。'])->create(['slug' => 'freshman-guide']);
    app(PublishWikiRevision::class)($linking, new PublishWikiRevisionDTO(
        title: '新生入門',
        description: null,
        body: '先看[[nou-exams-info]]。',
        categories: [],
        editSummary: null,
        origin: WikiRevisionOrigin::Admin,
    ));

    $this->get(route('wiki.show', 'nou-exams-info'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Wiki/Show')
            ->where('viewModel.page.title', '空大考試要去哪裡考？')
            ->where('viewModel.page.content', fn (string $html): bool => str_contains($html, 'wiki-link-missing'))
            ->where('viewModel.page.categories.0.name', '考試')
            ->where('viewModel.backlinks.0.slug', 'freshman-guide'));
});

it('redirects an old slug and 404s an unknown one', function (): void {
    $redirect = new WikiSlugRedirect;
    $redirect->old_slug = 'exams';
    $redirect->wiki_page_id = $this->page->id;
    $redirect->save();

    $this->get('/wiki/exams')->assertRedirect(route('wiki.show', 'nou-exams-info'))->assertStatus(301);
    $this->get('/wiki/missing-page')->assertNotFound();
});

it('serves the raw markdown twin', function (): void {
    $this->get('/wiki/nou-exams-info.md')
        ->assertOk()
        ->assertHeader('Content-Type', 'text/markdown; charset=utf-8')
        ->assertSee('# 空大考試要去哪裡考？', false)
        ->assertSee('[[credit-transfer|學分抵免]]', false);

    $this->get('/wiki.md')->assertOk()->assertSee('[空大考試要去哪裡考？]', false);
});

it('shows history, a revision with its diff, a category and the guides', function (): void {
    $this->get(route('wiki.history', 'nou-exams-info'))
        ->assertInertia(fn (Assert $page) => $page->component('Wiki/History')->has('viewModel.revisions', 1));

    $this->get(route('wiki.revision', ['slug' => 'nou-exams-info', 'revision' => $this->page->current_revision_id]))
        ->assertInertia(fn (Assert $page) => $page
            ->component('Wiki/Revision')
            ->where('viewModel.isFirstRevision', true)
            ->where('viewModel.diff.0.type', 'added'));

    $this->get(route('wiki.category', ['category' => '考試']))
        ->assertInertia(fn (Assert $page) => $page->component('Wiki/Category')->where('viewModel.pages.0.slug', 'nou-exams-info'));

    $this->get(route('wiki.guide', 'syntax'))
        ->assertInertia(fn (Assert $page) => $page->component('Wiki/Guide')->where('viewModel.title', '語法說明'));

    $this->get('/wiki/guide/other')->assertNotFound();
});

it('shows the edit form with sign-in state', function (): void {
    $this->get(route('wiki.edit', 'nou-exams-info'))
        ->assertInertia(fn (Assert $page) => $page
            ->component('Wiki/EditRequest/Create')
            ->where('viewModel.signedIn', false)
            ->where('viewModel.page.slug', 'nou-exams-info'));

    ($this->signedIn)()->get(route('wiki.new', ['slug' => 'credit-transfer', 'title' => '學分抵免']))
        ->assertInertia(fn (Assert $page) => $page
            ->where('viewModel.signedIn', true)
            ->where('viewModel.page', null)
            ->where('viewModel.requestedSlug', 'credit-transfer')
            ->where('viewModel.requestedTitle', '學分抵免'));
});

it('creates an edit request and drafts it after the response', function (): void {
    Bus::fake();
    ($this->passTurnstile)();

    $response = ($this->signedIn)()->post(route('wiki.edit-requests.store'), [
        'instruction' => '補充考試時間是上午九點。',
        'source_urls' => ['https://www.nou.edu.tw/calendar'],
        'page_slug' => 'nou-exams-info',
        'cf-turnstile-response' => 'token',
    ]);

    $editRequest = WikiEditRequest::query()->sole();

    $response->assertRedirect(route('wiki.edit-requests.show', $editRequest));

    expect($editRequest->wiki_page_id)->toBe($this->page->id)
        ->and($editRequest->base_revision_id)->toBe($this->page->current_revision_id)
        ->and($editRequest->student_schedule_id)->toBe($this->schedule->id)
        ->and($editRequest->source_urls)->toBe(['https://www.nou.edu.tw/calendar']);

    Bus::assertDispatchedAfterResponse(GenerateWikiEditDraft::class);
});

it('rejects requests without a device, from a blocked device, on protected pages or with foreign sources', function (Closure $setUp, array $payload, string $field): void {
    Bus::fake();
    ($this->passTurnstile)();

    $client = $setUp->call($this);

    $client->post(route('wiki.edit-requests.store'), [
        'instruction' => '補充考試時間是上午九點。',
        'source_urls' => [],
        'page_slug' => 'nou-exams-info',
        'cf-turnstile-response' => 'token',
        ...$payload,
    ])->assertSessionHasErrors($field);

    expect(WikiEditRequest::query()->count())->toBe(0);
})->with([
    'no device' => [fn () => $this, [], 'instruction'],
    'blocked' => [function () {
        $this->schedule->wiki_blocked_at = now();
        $this->schedule->save();

        return ($this->signedIn)();
    }, [], 'instruction'],
    'protected' => [function () {
        $this->page->is_protected = true;
        $this->page->save();

        return ($this->signedIn)();
    }, [], 'instruction'],
    'foreign source' => [fn () => ($this->signedIn)(), ['source_urls' => ['https://example.com/a']], 'source_urls.0'],
    'too many sources' => [fn () => ($this->signedIn)(), ['source_urls' => ['https://www.nou.edu.tw/1', 'https://www.nou.edu.tw/2', 'https://www.nou.edu.tw/3', 'https://www.nou.edu.tw/4']], 'source_urls'],
    'bad slug hint' => [fn () => ($this->signedIn)(), ['page_slug' => null, 'requested_slug' => 'Not A Slug'], 'requested_slug'],
]);

it('requires turnstile', function (): void {
    ($this->signedIn)()->post(route('wiki.edit-requests.store'), [
        'instruction' => '補充考試時間是上午九點。',
        'source_urls' => [],
    ])->assertSessionHasErrors('cf-turnstile-response');
});

it('limits drafts per device', function (): void {
    Bus::fake();
    ($this->passTurnstile)();
    config(['wiki.rate_limits.per_schedule_per_hour' => 2]);

    foreach (range(1, 3) as $attempt) {
        $response = ($this->signedIn)()->post(route('wiki.edit-requests.store'), [
            'instruction' => "第 {$attempt} 次修改請求。",
            'source_urls' => [],
            'cf-turnstile-response' => 'token',
        ]);
    }

    $response->assertSessionHasErrors(['instruction' => '你這一小時內的修改次數已達上限，請稍後再試。']);
    expect(WikiEditRequest::query()->count())->toBe(2);
});

it('stops at the site-wide daily cap', function (): void {
    Bus::fake();
    ($this->passTurnstile)();
    config(['wiki.ai.daily_cap' => 0]);

    ($this->signedIn)()->post(route('wiki.edit-requests.store'), [
        'instruction' => '新增一個條目。',
        'source_urls' => [],
        'cf-turnstile-response' => 'token',
    ])->assertSessionHasErrors(['instruction' => '知識庫今天的 AI 編輯額度已用完，請明天再試。']);
});

it('shows a request to anyone but lets only its device act on it', function (): void {
    $editRequest = WikiEditRequest::factory()->forPage($this->page)->awaitingConfirmation()->create([
        'student_schedule_id' => $this->schedule->id,
    ]);

    $this->get(route('wiki.edit-requests.show', $editRequest))
        ->assertInertia(fn (Assert $page) => $page
            ->component('Wiki/EditRequest/Show')
            ->where('viewModel.request.isOwner', false)
            ->where('viewModel.request.status', 'awaiting_confirmation')
            ->where('viewModel.request.draftContent', fn (string $html): bool => str_contains($html, '更新後的內容'))
            ->where('viewModel.request.diff', fn ($diff): bool => collect($diff)->contains('type', 'added')));

    $this->post(route('wiki.edit-requests.submit', $editRequest))->assertForbidden();

    ($this->signedIn)()->get(route('wiki.edit-requests.show', $editRequest))
        ->assertInertia(fn (Assert $page) => $page->where('viewModel.request.isOwner', true));
});

it('submits, refines, redrafts and abandons through the owner device', function (): void {
    Bus::fake();

    $refining = WikiEditRequest::factory()->forPage($this->page)->awaitingConfirmation()->create(['student_schedule_id' => $this->schedule->id]);

    ($this->signedIn)()->post(route('wiki.edit-requests.refine', $refining), ['instruction' => '再短一點'])
        ->assertRedirect(route('wiki.edit-requests.show', $refining));

    expect($refining->refresh()->status)->toBe(WikiEditRequestStatus::Drafting)
        ->and($refining->refinements)->toBe(['再短一點']);

    Bus::assertDispatchedAfterResponse(GenerateWikiEditDraft::class);

    $conflicted = WikiEditRequest::factory()->forPage($this->page)->awaitingConfirmation()->create([
        'student_schedule_id' => $this->schedule->id,
        'status' => WikiEditRequestStatus::Conflict,
        'refinements' => ['加上例子'],
    ]);

    ($this->signedIn)()->post(route('wiki.edit-requests.redraft', $conflicted))->assertRedirect();

    expect($conflicted->refresh()->status)->toBe(WikiEditRequestStatus::Drafting)
        ->and($conflicted->instruction)->toContain('加上例子')
        ->and($conflicted->draft_body)->toBeNull();

    $submitting = WikiEditRequest::factory()->forPage($this->page)->awaitingConfirmation([
        'draft_title' => '空大考試要去哪裡考？',
        'draft_categories' => ['考試'],
        'ai_verdict' => ['risk' => 'high'],
    ])->create(['student_schedule_id' => $this->schedule->id]);

    ($this->signedIn)()->post(route('wiki.edit-requests.submit', $submitting))->assertRedirect();

    expect($submitting->refresh()->status)->toBe(WikiEditRequestStatus::PendingReview);

    $abandoning = WikiEditRequest::factory()->forPage($this->page)->awaitingConfirmation()->create(['student_schedule_id' => $this->schedule->id]);

    ($this->signedIn)()->delete(route('wiki.edit-requests.destroy', $abandoning))->assertRedirect(route('wiki.show', 'nou-exams-info'));

    expect($abandoning->refresh()->status)->toBe(WikiEditRequestStatus::Abandoned);
});

it('caps refinements', function (): void {
    config(['wiki.max_refinements' => 1]);

    $editRequest = WikiEditRequest::factory()->forPage($this->page)->awaitingConfirmation()->create([
        'student_schedule_id' => $this->schedule->id,
        'refinements' => ['一次'],
    ]);

    ($this->signedIn)()->post(route('wiki.edit-requests.refine', $editRequest), ['instruction' => '再一次'])
        ->assertSessionHasErrors('instruction');
});

it('redirects the old knowledge base to the wiki', function (): void {
    $this->get('/kb')->assertStatus(301)->assertRedirect('/wiki');
    $this->get('/kb/about-nou')->assertStatus(301)->assertRedirect('/wiki/about-nou');
    $this->get('/kb/about-nou.md')->assertStatus(301)->assertRedirect('/wiki/about-nou.md');
});

it('imported the old about-nou page', function (): void {
    $this->get('/wiki/about-nou')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->where('viewModel.page.title', '關於國立空中大學'));
});

it('server-renders seo tags for a page and keeps edit forms out of the index', function (): void {
    $this->get(route('wiki.show', 'nou-exams-info'))
        ->assertSee('<title>空大考試要去哪裡考？ - 空大知識庫 - NOU 小幫手</title>', false)
        ->assertSee('<meta data-seo name="description" content="期中考與期末考都在週末舉行。" />', false)
        ->assertSee('<template data-og-image', false);

    $this->get(route('wiki.edit', 'nou-exams-info'))
        ->assertSee('<meta data-seo name="robots" content="noindex, nofollow" />', false);
});
