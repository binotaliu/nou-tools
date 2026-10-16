<?php

declare(strict_types=1);

use App\Enums\WikiEditRequestStatus;
use App\Enums\WikiRevisionOrigin;
use App\Models\StudentSchedule;
use App\Models\User;
use App\Models\WikiCategory;
use App\Models\WikiEditRequest;
use App\Models\WikiLink;
use App\Models\WikiPage;
use App\Models\WikiRevision;
use App\Notifications\WikiEditRequestNeedsReview;
use Illuminate\Support\Facades\Notification;
use NouTools\Domains\Wiki\Actions\ApproveWikiEditRequest;
use NouTools\Domains\Wiki\Actions\PublishWikiRevision;
use NouTools\Domains\Wiki\Actions\RejectWikiEditRequest;
use NouTools\Domains\Wiki\Actions\ResolveWikiPublishDecision;
use NouTools\Domains\Wiki\Actions\SubmitWikiEditRequest;
use NouTools\Domains\Wiki\DataTransferObjects\PublishWikiRevisionDTO;
use NouTools\Domains\Wiki\Support\WikiTextDiff;

const WIKI_BASE_BODY = "期中考與期末考都在週末舉行。\n\n## 考場\n\n各學習指導中心。\n\n## 時間\n\n上午九點開始。\n\n## 攜帶物品\n\n學生證與文具。\n\n## 注意事項\n\n遲到二十分鐘不得入場。\n";

function wikiRevisionData(array $overrides = []): PublishWikiRevisionDTO
{
    return new PublishWikiRevisionDTO(...[
        'title' => '考試',
        'description' => null,
        'body' => '內容。',
        'categories' => [],
        'editSummary' => '建立',
        'origin' => WikiRevisionOrigin::Admin,
        ...$overrides,
    ]);
}

beforeEach(function (): void {
    $this->page = WikiPage::factory()->live([
        'title' => '空大考試要去哪裡考？',
        'body' => WIKI_BASE_BODY,
        'categories' => ['考試'],
        'created_at' => now()->subDay(),
    ])->create(['slug' => 'nou-exams-info']);

    $this->smallEdit = fn (array $draft = []): WikiEditRequest => WikiEditRequest::factory()
        ->forPage($this->page)
        ->awaitingConfirmation([
            'draft_title' => '空大考試要去哪裡考？',
            'draft_categories' => ['考試'],
            'draft_body' => str_replace('上午九點開始。', '上午九點開始，請提早十分鐘到場。', WIKI_BASE_BODY),
            ...$draft,
        ])
        ->create();
});

it('publishes a new page with indexes', function (): void {
    $revision = app(PublishWikiRevision::class)(null, wikiRevisionData([
        'body' => '詳見[[nou-exams-info]]與[[credit-transfer|抵免]]，以及自己[[credit-rules]]。',
        'categories' => ['選課', '學分'],
    ]), 'credit-rules');

    $page = WikiPage::query()->where('slug', 'credit-rules')->firstOrFail();

    expect($page->current_revision_id)->toBe($revision->id)
        ->and($page->categories->pluck('name')->all())->toBe(['學分', '選課'])
        ->and(WikiLink::query()->where('from_page_id', $page->id)->pluck('to_slug')->sort()->values()->all())
        ->toBe(['credit-transfer', 'nou-exams-info']);
});

it('chains revisions and drops empty categories', function (): void {
    $first = $this->page->current_revision_id;

    app(PublishWikiRevision::class)($this->page, wikiRevisionData(['categories' => ['報名']]));

    expect($this->page->fresh()->currentRevision->parent_revision_id)->toBe($first)
        ->and(WikiCategory::query()->pluck('name')->all())->toBe(['報名']);
});

it('leaves a redirect when the slug changes and can take it back', function (): void {
    app(PublishWikiRevision::class)($this->page, wikiRevisionData(), 'exams');

    expect($this->page->fresh()->slug)->toBe('exams')
        ->and(WikiPage::isSlugAvailable('nou-exams-info'))->toBeFalse();

    app(PublishWikiRevision::class)($this->page->fresh(), wikiRevisionData(), 'nou-exams-info');

    expect($this->page->fresh()->slug)->toBe('nou-exams-info')
        ->and($this->page->slugRedirects()->pluck('old_slug')->all())->toBe(['exams']);
});

it('refuses a taken slug', function (): void {
    app(PublishWikiRevision::class)(null, wikiRevisionData(), 'nou-exams-info');
})->throws(DomainException::class);

it('auto-publishes a small, low-risk edit', function (): void {
    $editRequest = ($this->smallEdit)();

    app(SubmitWikiEditRequest::class)($editRequest);

    $editRequest->refresh();

    expect($editRequest->status)->toBe(WikiEditRequestStatus::Published)
        ->and($this->page->fresh()->current_revision_id)->toBe($editRequest->published_revision_id)
        ->and($editRequest->publishedRevision->origin)->toBe(WikiRevisionOrigin::AiEdit)
        ->and($editRequest->publishedRevision->student_schedule_id)->toBe($editRequest->student_schedule_id);
});

it('sends edits to review for each rule', function (Closure $setUp, string $reason): void {
    $editRequest = $setUp->call($this);

    $decision = app(ResolveWikiPublishDecision::class)($editRequest);

    expect($decision->publishesAutomatically())->toBeFalse()
        ->and(implode('|', $decision->reviewReasons))->toContain($reason);
})->with([
    'medium risk' => [fn () => ($this->smallEdit)(['ai_verdict' => ['risk' => 'medium']]), 'AI 評估風險為中'],
    'title change' => [fn () => ($this->smallEdit)(['draft_title' => '考試資訊']), '變更標題'],
    'category change' => [fn () => ($this->smallEdit)(['draft_categories' => ['考試', '新生']]), '變更分類'],
    'large change' => [fn () => ($this->smallEdit)(['draft_body' => "全部重寫。\n\n## 新段落\n\n新內容。\n"]), '修改幅度'],
    'protected' => [function () {
        $this->page->is_protected = true;
        $this->page->save();

        return ($this->smallEdit)();
    }, '受保護的條目'],
    'disabled' => [function () {
        config(['wiki.auto_publish.enabled' => false]);

        return ($this->smallEdit)();
    }, '自動發布已關閉'],
    'recent edit by someone else' => [function () {
        WikiRevision::factory()->for($this->page, 'page')->create(['student_schedule_id' => StudentSchedule::factory()]);

        return ($this->smallEdit)();
    }, '其他人剛編輯過此條目'],
    'new page' => [fn () => WikiEditRequest::factory()->awaitingConfirmation(['draft_slug' => 'credit-transfer'])->create(), '新條目'],
]);

it('measures removed text, not reworded text', function (): void {
    $diff = WikiTextDiff::compare("一\n二\n三\n", "一\n二改\n三\n");

    expect($diff->addedLines)->toBe(['二改'])
        ->and($diff->removedLines)->toBe(['二'])
        ->and($diff->removedChars())->toBe(0)
        ->and(WikiTextDiff::compare("一\n很長的一段文字\n", "一\n")->removedChars())->toBe(7);
});

it('queues a reviewed edit and notifies discord', function (): void {
    Notification::fake();
    config(['services.discord.webhooks.wiki_review' => 'https://discord.test/webhook']);

    $editRequest = ($this->smallEdit)(['ai_verdict' => ['risk' => 'high']]);

    app(SubmitWikiEditRequest::class)($editRequest);

    expect($editRequest->refresh()->status)->toBe(WikiEditRequestStatus::PendingReview)
        ->and($editRequest->ai_verdict['reviewReasons'])->toContain('AI 評估風險為高');

    Notification::assertSentOnDemand(WikiEditRequestNeedsReview::class);
});

it('marks a stale draft as a conflict', function (): void {
    $editRequest = ($this->smallEdit)();
    app(PublishWikiRevision::class)($this->page, wikiRevisionData());

    app(SubmitWikiEditRequest::class)($editRequest);

    expect($editRequest->refresh()->status)->toBe(WikiEditRequestStatus::Conflict);
});

it('refuses to submit twice', function (): void {
    $editRequest = ($this->smallEdit)();
    app(SubmitWikiEditRequest::class)($editRequest);
    app(SubmitWikiEditRequest::class)($editRequest);
})->throws(DomainException::class);

it('lets an admin approve a new page under another slug', function (): void {
    $admin = User::factory()->create();
    $editRequest = WikiEditRequest::factory()->awaitingConfirmation([
        'draft_slug' => 'credit-transfer',
        'draft_title' => '學分抵免',
        'draft_categories' => ['選課'],
    ])->create(['status' => WikiEditRequestStatus::PendingReview]);

    app(ApproveWikiEditRequest::class)($editRequest, $admin, 'credit-transfer-rules');

    $editRequest->refresh();

    expect($editRequest->status)->toBe(WikiEditRequestStatus::Published)
        ->and($editRequest->page->slug)->toBe('credit-transfer-rules')
        ->and($editRequest->reviewed_by)->toBe($admin->id)
        ->and($editRequest->publishedRevision->user_id)->toBe($admin->id);
});

it('refuses to approve a stale edit', function (): void {
    $editRequest = ($this->smallEdit)(['status' => WikiEditRequestStatus::PendingReview]);
    app(PublishWikiRevision::class)($this->page, wikiRevisionData());

    app(ApproveWikiEditRequest::class)($editRequest, User::factory()->create());
})->throws(DomainException::class);

it('lets an admin reject with a note', function (): void {
    $editRequest = ($this->smallEdit)(['status' => WikiEditRequestStatus::PendingReview]);

    app(RejectWikiEditRequest::class)($editRequest, User::factory()->create(), '來源不足');

    expect($editRequest->refresh()->status)->toBe(WikiEditRequestStatus::Rejected)
        ->and($editRequest->review_note)->toBe('來源不足');
});
