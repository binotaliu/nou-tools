<?php

use App\Enums\WikiEditRequestStatus;
use App\Models\ScheduleDevice;
use App\Models\StudentSchedule;
use App\Models\WikiEditRequest;
use App\Models\WikiPage;
use NouTools\Domains\Wiki\Actions\DraftAndSubmitWikiEdit;
use NouTools\Domains\Wiki\Ai\WikiEditor;

// The whole loop needs a real browser: the device cookie is HttpOnly, the
// draft is written after the response (the faked editor answers at once)
// and the status page only shows it by polling.

beforeEach(function (): void {
    $this->page = WikiPage::factory()->live([
        'title' => '空大考試要去哪裡考？',
        'body' => "期中考與期末考都在週末舉行。\n\n## 考場\n\n各學習指導中心。\n\n## 時間\n\n上午九點開始。\n\n## 攜帶物品\n\n學生證與文具。\n",
        'categories' => ['考試'],
        'created_at' => now()->subDay(),
    ])->create(['slug' => 'nou-exams-info']);

    $this->signIn = function (): mixed {
        $schedule = StudentSchedule::factory()->create();
        $browser = visit(route('schedules.my'));

        $browser->click('[data-testid="find-schedule-existing"]')
            ->fill('[data-testid="find-schedule-url"]', $schedule->getRouteKey())
            ->click('[data-testid="find-schedule-submit"]');

        waitUntil($browser, "location.pathname !== '/schedules/my'");

        return $browser;
    };
});

it('asks a visitor without a schedule to sign in first', function (): void {
    visit('/wiki/nou-exams-info/edit')
        ->assertNoJavaScriptErrors()
        ->assertVisible('[data-testid="wiki-needs-schedule"]')
        ->assertMissing('[data-testid="wiki-edit-request-form"]');
});

it('shows the editor at work, then the published edit with its diff, preview and refine form', function (): void {
    WikiEditor::fake([[
        'decision' => 'apply',
        'decline_reason' => '',
        'slug' => '',
        'title' => '空大考試要去哪裡考？',
        'description' => '',
        'body' => "期中考與期末考都在週末舉行。\n\n## 考場\n\n各學習指導中心。\n\n## 時間\n\n上午九點半開始。\n\n## 攜帶物品\n\n學生證與文具。\n",
        'categories' => ['考試'],
        'edit_summary' => '更新考試開始時間',
        'risk' => 'low',
        'risk_reasons' => [],
        'sources_used' => [],
    ]]);

    $browser = ($this->signIn)();

    $browser->navigate('/wiki/nou-exams-info/edit')
        ->assertVisible('[data-testid="wiki-edit-request-form"]');

    // Submitting the form needs a solved Turnstile challenge, which a test
    // can't produce (the feature tests cover the POST). Start from the
    // request it would have created, still drafting, so the page polls.
    $editRequest = WikiEditRequest::factory()->forPage($this->page)->create([
        'instruction' => '考試改成上午九點半開始。',
        'student_schedule_id' => ScheduleDevice::query()->latest('id')->value('student_schedule_id'),
    ]);

    $browser->navigate(route('wiki.edit-requests.show', $editRequest))
        ->assertVisible('[data-testid="wiki-request-drafting"]')
        ->assertVisible('[data-testid="wiki-drafting-step"]');

    // What the job does after the response: no 確認送出 in between.
    app(DraftAndSubmitWikiEdit::class)($editRequest);

    waitUntil($browser, 'document.querySelector(\'[data-testid="wiki-request-published"]\') !== null', 10000);

    expect($editRequest->refresh()->status)->toBe(WikiEditRequestStatus::Published);

    $browser->assertSee('更新考試開始時間')
        ->assertPresent('[data-diff-type="added"]')
        ->assertMissing('[data-testid="wiki-request-submit"]')
        ->assertVisible('[data-testid="wiki-refine-instruction"]')
        ->click('[data-testid="wiki-tab-preview"]')
        ->assertVisible('[data-testid="wiki-draft-preview"]')
        ->assertNoJavaScriptErrors();

    $browser->navigate('/wiki/nou-exams-info')
        ->assertSeeIn('[data-testid="wiki-page-content"]', '上午九點半開始');
});

it('turns a link to a missing page into the new page form', function (): void {
    $linking = WikiPage::factory()->live(['title' => '新生入門', 'body' => '請先了解[[credit-transfer|學分抵免]]。'])->create(['slug' => 'freshman-guide']);

    $browser = visit('/wiki/'.$linking->slug);

    $browser->assertNoJavaScriptErrors()
        ->click('a.wiki-link-missing');

    waitUntil($browser, "location.pathname === '/wiki/new'");

    expect($browser->script('location.search'))->toBe('?slug=credit-transfer');
});

it('hydrates markdown containers on wiki pages, also after navigating between pages', function (): void {
    WikiPage::factory()->live([
        'title' => '分頁測試',
        'body' => "::::tabs\n:::tab 第一頁\n第一頁內容\n:::\n:::tab 第二頁\n第二頁內容\n:::\n::::\n\n[[nou-exams-info]]\n",
    ])->create(['slug' => 'tabs-fixture']);
    WikiPage::factory()->live([
        'title' => '第二個分頁測試',
        'body' => "::::tabs\n:::tab 甲\n甲內容\n:::\n:::tab 乙\n乙內容\n:::\n::::\n",
    ])->create(['slug' => 'tabs-fixture-two']);

    $browser = visit('/wiki/tabs-fixture');

    waitUntil($browser, "document.querySelector('[data-testid=\"wiki-page-content\"] [data-enhanced]') !== null");

    $browser->navigate('/wiki/tabs-fixture-two');

    waitUntil($browser, "document.querySelector('[data-testid=\"wiki-page-content\"] [data-enhanced]') !== null");

    $browser->assertNoJavaScriptErrors();
});
