<?php

declare(strict_types=1);

use App\Enums\WikiEditEventType;
use App\Enums\WikiEditRequestStatus;
use App\Jobs\GenerateWikiEditDraft;
use App\Models\ScheduleDevice;
use App\Models\StudentSchedule;
use App\Models\User;
use App\Models\WikiEditEvent;
use App\Models\WikiEditRequest;
use App\Models\WikiPage;
use App\Models\WikiRevision;
use App\Notifications\WikiEditRequestNotPublished;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Notification;
use Inertia\Testing\AssertableInertia as Assert;
use NouTools\Domains\Schedules\Actions\BuildScheduleDeviceCookie;
use NouTools\Domains\Wiki\Actions\ApproveWikiEditRequest;
use NouTools\Domains\Wiki\Actions\DraftAndSubmitWikiEdit;
use NouTools\Domains\Wiki\Actions\RejectWikiEditRequest;
use NouTools\Domains\Wiki\Ai\WikiEditor;

beforeEach(function (): void {
    Notification::fake();

    $this->baseBody = "期中考與期末考都在週末舉行。\n\n## 考場\n\n各學習指導中心。\n\n## 時間\n\n上午九點開始。\n\n## 攜帶物品\n\n學生證與文具。\n\n## 注意事項\n\n遲到二十分鐘不得入場。\n";

    $this->response = fn (array $overrides = []): array => [
        'decision' => 'apply',
        'decline_reason' => '',
        'slug' => '',
        'title' => '空大考試要去哪裡考？',
        'description' => '期中考與期末考都在週末舉行。',
        'body' => str_replace('上午九點開始。', '上午九點開始，請提早十分鐘到場。', $this->baseBody),
        'categories' => ['考試'],
        'edit_summary' => '補充到場時間',
        'risk' => 'low',
        'risk_reasons' => [],
        'sources_used' => [],
        ...$overrides,
    ];

    $this->page = WikiPage::factory()->live([
        'title' => '空大考試要去哪裡考？',
        'body' => $this->baseBody,
        'categories' => ['考試'],
        'created_at' => now()->subDay(),
    ])->create(['slug' => 'nou-exams-info']);

    $this->schedule = StudentSchedule::factory()->create();
    ScheduleDevice::factory()->for($this->schedule)->withToken('wiki-device')->create();
    $this->signedIn = fn () => $this->withCookie(BuildScheduleDeviceCookie::NAME, 'wiki-device');

    $this->drafting = fn (array $attributes = []): WikiEditRequest => WikiEditRequest::factory()
        ->forPage($this->page)
        ->create(['student_schedule_id' => $this->schedule->id, ...$attributes]);
});

it('publishes a small, low-risk draft as soon as it is written', function (): void {
    WikiEditor::fake([($this->response)()]);

    $editRequest = app(DraftAndSubmitWikiEdit::class)(($this->drafting)());

    expect($editRequest->refresh()->status)->toBe(WikiEditRequestStatus::Published)
        ->and($this->page->fresh()->current_revision_id)->toBe($editRequest->published_revision_id);
});

it('never reaches the real discord webhooks from .env', function (): void {
    expect(config('services.discord.webhooks'))->each->toBeEmpty();
});

it('queues a draft the rules hold back without waiting for the visitor', function (): void {
    WikiEditor::fake([($this->response)(['risk' => 'medium'])]);

    $editRequest = app(DraftAndSubmitWikiEdit::class)(($this->drafting)());

    expect($editRequest->refresh()->status)->toBe(WikiEditRequestStatus::PendingReview)
        ->and($editRequest->ai_verdict['reviewReasons'])->toContain('AI 評估風險為中');
});

it('leaves a declined request alone', function (): void {
    WikiEditor::fake([($this->response)(['decision' => 'decline', 'decline_reason' => '沒有來源'])]);

    $editRequest = app(DraftAndSubmitWikiEdit::class)(($this->drafting)());

    expect($editRequest->refresh()->status)->toBe(WikiEditRequestStatus::DeclinedByAi);
});

it('refines a published edit with a further revision on top of it', function (): void {
    WikiEditor::fake([
        ($this->response)(),
        ($this->response)(['body' => str_replace('上午九點開始。', '上午九點開始，請提早十五分鐘到場。', $this->baseBody)]),
    ]);

    $editRequest = app(DraftAndSubmitWikiEdit::class)(($this->drafting)());
    $firstRevision = $editRequest->published_revision_id;

    expect($editRequest->fresh()->canBeRefined())->toBeTrue();

    // The refinement is drafted and submitted after the response, which the
    // test client runs before returning.
    ($this->signedIn)()->post(route('wiki.edit-requests.refine', $editRequest), ['instruction' => '改成十五分鐘'])
        ->assertRedirect(route('wiki.edit-requests.show', $editRequest));

    $editRequest->refresh();
    $page = $this->page->fresh();

    expect($editRequest->status)->toBe(WikiEditRequestStatus::Published)
        ->and($editRequest->published_revision_id)->not->toBe($firstRevision)
        ->and($page->current_revision_id)->toBe($editRequest->published_revision_id)
        ->and($page->currentRevision->parent_revision_id)->toBe($firstRevision)
        ->and($page->currentRevision->body)->toContain('十五分鐘')
        ->and(WikiRevision::query()->where('wiki_edit_request_id', $editRequest->id)->count())->toBe(2);

    WikiEditor::assertPrompted(fn ($prompt): bool => str_contains($prompt->prompt, "<refinements>\n1. 改成十五分鐘\n</refinements>")
        && str_contains($prompt->prompt, '請提早十分鐘到場'));
});

it('keeps a published edit as it was when a refinement fails', function (): void {
    WikiEditor::fake([($this->response)(), fn () => throw new RuntimeException('overloaded')]);

    $editRequest = app(DraftAndSubmitWikiEdit::class)(($this->drafting)());
    $publishedRevision = $editRequest->published_revision_id;

    ($this->signedIn)()->post(route('wiki.edit-requests.refine', $editRequest), ['instruction' => '再短一點']);

    $editRequest->refresh();

    expect($editRequest->status)->toBe(WikiEditRequestStatus::Published)
        ->and($editRequest->refinements)->toBe([])
        ->and($editRequest->ai_verdict['lastRefinementFailed'])->toBeTrue()
        ->and($this->page->fresh()->current_revision_id)->toBe($publishedRevision);
});

it('stops refining a published edit once someone else edited the page', function (): void {
    WikiEditor::fake([($this->response)()]);

    $editRequest = app(DraftAndSubmitWikiEdit::class)(($this->drafting)());

    $this->page->forceFill(['current_revision_id' => WikiRevision::factory()->for($this->page, 'page')->create()->id])->save();

    expect($editRequest->fresh()->canBeRefined())->toBeFalse();

    ($this->signedIn)()->post(route('wiki.edit-requests.refine', $editRequest), ['instruction' => '再短一點'])
        ->assertSessionHasErrors('instruction');
});

it('approves a refinement of a published edit against what went live', function (): void {
    WikiEditor::fake([
        ($this->response)(),
        ($this->response)(['title' => '空大考試資訊']),
    ]);

    $editRequest = app(DraftAndSubmitWikiEdit::class)(($this->drafting)());
    ($this->signedIn)()->post(route('wiki.edit-requests.refine', $editRequest), ['instruction' => '標題短一點']);

    expect($editRequest->refresh()->status)->toBe(WikiEditRequestStatus::PendingReview)
        ->and($editRequest->ai_verdict['reviewReasons'])->toContain('變更標題');

    app(ApproveWikiEditRequest::class)($editRequest, User::factory()->create());

    expect($this->page->fresh()->currentRevision->title)->toBe('空大考試資訊');
});

it('lets the visitor withdraw an edit waiting for review', function (): void {
    $editRequest = ($this->drafting)(['status' => WikiEditRequestStatus::PendingReview]);

    ($this->signedIn)()->delete(route('wiki.edit-requests.destroy', $editRequest))
        ->assertRedirect(route('wiki.show', 'nou-exams-info'));

    expect($editRequest->refresh()->status)->toBe(WikiEditRequestStatus::Abandoned);
});

it('drafts and submits a refinement after the response', function (): void {
    Bus::fake();

    ($this->signedIn)()->post(route('wiki.edit-requests.refine', WikiEditRequest::factory()->forPage($this->page)->awaitingConfirmation()->create([
        'student_schedule_id' => $this->schedule->id,
        'status' => WikiEditRequestStatus::PendingReview,
    ])), ['instruction' => '再短一點']);

    Bus::assertDispatched(GenerateWikiEditDraft::class);
});

it('offers refining and withdrawing only to the owner', function (): void {
    $editRequest = WikiEditRequest::factory()->forPage($this->page)->awaitingConfirmation()->create([
        'student_schedule_id' => $this->schedule->id,
        'status' => WikiEditRequestStatus::PendingReview,
    ]);

    $this->get(route('wiki.edit-requests.show', $editRequest))
        ->assertInertia(fn (Assert $page) => $page
            ->where('viewModel.request.canRefine', false)
            ->where('viewModel.request.canAbandon', false));

    ($this->signedIn)()->get(route('wiki.edit-requests.show', $editRequest))
        ->assertInertia(fn (Assert $page) => $page
            ->where('viewModel.request.canRefine', true)
            ->where('viewModel.request.canAbandon', true));
});

it('logs a declined edit and tells the webhook', function (): void {
    config(['services.discord.webhooks.wiki_review' => 'https://discord.test/webhook']);
    WikiEditor::fake([($this->response)(['decision' => 'decline', 'decline_reason' => '沒有來源'])]);

    $editRequest = app(DraftAndSubmitWikiEdit::class)(($this->drafting)());

    $event = WikiEditEvent::query()->sole();

    expect($event->event)->toBe(WikiEditEventType::Declined)
        ->and($event->detail)->toBe('沒有來源')
        ->and($event->wiki_edit_request_id)->toBe($editRequest->id)
        ->and($event->page_slug)->toBe('nou-exams-info')
        ->and($event->student_schedule_id)->toBe($this->schedule->id);

    Notification::assertSentOnDemand(WikiEditRequestNotPublished::class);
});

it('logs a failed draft and tells the webhook', function (): void {
    config(['services.discord.webhooks.wiki_review' => 'https://discord.test/webhook']);
    WikiEditor::fake([($this->response)(['body' => $this->baseBody."\n[外部](https://evil.example.com/x)\n"])]);

    app(DraftAndSubmitWikiEdit::class)(($this->drafting)());

    expect(WikiEditEvent::query()->sole()->event)->toBe(WikiEditEventType::Failed);
    Notification::assertSentOnDemand(WikiEditRequestNotPublished::class);
});

it('logs a rejection by the reviewer', function (): void {
    config(['services.discord.webhooks.wiki_review' => 'https://discord.test/webhook']);
    WikiEditor::fake([($this->response)(['risk' => 'medium'])]);
    $editRequest = app(DraftAndSubmitWikiEdit::class)(($this->drafting)());
    $reviewer = User::factory()->create();

    app(RejectWikiEditRequest::class)($editRequest, $reviewer, '來源不足');

    $event = WikiEditEvent::query()->where('event', WikiEditEventType::Rejected)->sole();

    expect($event->user_id)->toBe($reviewer->id)
        ->and($event->detail)->toBe('來源不足');
    Notification::assertSentOnDemand(WikiEditRequestNotPublished::class);
});

it('logs a success without raising the unsuccessful-edit webhook', function (): void {
    config(['services.discord.webhooks.wiki_review' => 'https://discord.test/webhook']);
    WikiEditor::fake([($this->response)()]);

    app(DraftAndSubmitWikiEdit::class)(($this->drafting)());

    expect(WikiEditEvent::query()->sole()->event)->toBe(WikiEditEventType::Published);
    Notification::assertNotSentTo(
        new AnonymousNotifiable,
        WikiEditRequestNotPublished::class,
    );
});

it('marks a request failed when its queue job dies', function (): void {
    $editRequest = ($this->drafting)();

    (new GenerateWikiEditDraft($editRequest))->failed(new RuntimeException('timed out'));

    expect($editRequest->refresh()->status)->toBe(WikiEditRequestStatus::Failed)
        ->and(WikiEditEvent::query()->sole()->event)->toBe(WikiEditEventType::Failed);
});
