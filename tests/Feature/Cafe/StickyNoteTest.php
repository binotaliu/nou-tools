<?php

use App\Enums\StickyNoteStatus;
use App\Events\CafeUpdated;
use App\Jobs\ScreenStickyNoteJob;
use App\Models\AcornTransaction;
use App\Models\CafeOrder;
use App\Models\CafeStickyNote;
use App\Models\StudentSchedule;
use App\Models\StudyRoomProfile;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use NouTools\Domains\Acorns\Actions\ReadAcornBalance;
use NouTools\Domains\Cafe\Actions\DeclineStickyNote;
use NouTools\Domains\Cafe\Actions\ReportStickyNote;
use NouTools\Domains\Cafe\Actions\ScreenStickyNote;
use NouTools\Domains\Cafe\Ai\StickyNoteScreener;

$cookieFor = function (StudentSchedule $schedule): string {
    return json_encode(['id' => $schedule->id, 'uuid' => $schedule->uuid, 'name' => $schedule->name]);
};

// A student who bought a coffee today and holds acorns.
$buyer = function (int $acorns = 10, bool $coffee = true): StudentSchedule {
    $schedule = StudentSchedule::factory()->create();
    StudyRoomProfile::factory()->for($schedule, 'schedule')->create(['nickname' => '便利貼同學']);
    AcornTransaction::factory()->for($schedule, 'schedule')->credit($acorns)->create();

    if ($coffee) {
        CafeOrder::factory()->for($schedule, 'schedule')->create();
    }

    return $schedule;
};

$payload = fn (array $overrides = []): array => [
    'text' => '期中考加油',
    'paperColor' => 'pink',
    'x' => 0.4,
    'y' => 0.6,
    'rotation' => 3,
    ...$overrides,
];

beforeEach(function () {
    Storage::fake('cafe_sticky_notes');
    Queue::fake();
    Event::fake([CafeUpdated::class]);
});

it('sells a note for acorns after a coffee today and queues the screening', function () use ($buyer, $cookieFor, $payload) {
    $schedule = $buyer();

    $this->withCredentials()->withCookie('student_schedule', $cookieFor($schedule))
        ->postJson(route('cafe.notes.store'), $payload())
        ->assertCreated()
        ->assertJsonPath('note.status', 'screening')
        ->assertJsonPath('note.isMine', true)
        ->assertJsonPath('acorns.balance', 5);

    $note = CafeStickyNote::query()->firstOrFail();

    expect($note->status)->toBe(StickyNoteStatus::Screening)
        ->and($note->acorn_transaction_id)->not->toBeNull();

    Queue::assertPushed(ScreenStickyNoteJob::class, fn (ScreenStickyNoteJob $job): bool => $job->note->is($note));
});

it('stores a 480 square png drawing privately and rejects other sizes or types', function () use ($buyer, $cookieFor, $payload) {
    $schedule = $buyer(20);
    $cookie = $cookieFor($schedule);

    $this->withCredentials()->withCookie('student_schedule', $cookie)
        ->postJson(route('cafe.notes.store'), $payload(['text' => null, 'drawing' => UploadedFile::fake()->image('d.png', 100, 100)]))
        ->assertUnprocessable()->assertJsonValidationErrors('drawing');

    $this->withCredentials()->withCookie('student_schedule', $cookie)
        ->postJson(route('cafe.notes.store'), $payload(['text' => null, 'drawing' => UploadedFile::fake()->image('d.jpg', 480, 480)]))
        ->assertUnprocessable()->assertJsonValidationErrors('drawing');

    $this->withCredentials()->withCookie('student_schedule', $cookie)
        ->postJson(route('cafe.notes.store'), $payload(['text' => null, 'drawing' => UploadedFile::fake()->image('d.png', 480, 480)]))
        ->assertCreated();

    $path = CafeStickyNote::query()->firstOrFail()->drawing_path;

    expect($path)->toEndWith('.png')->not->toContain('d.png');
    Storage::disk('cafe_sticky_notes')->assertExists($path);
});

it('needs text or a drawing, a coffee today, acorns, and only one a day', function () use ($buyer, $cookieFor, $payload) {
    $post = fn (StudentSchedule $schedule, array $body) => $this->withCredentials()
        ->withCookie('student_schedule', $cookieFor($schedule))
        ->postJson(route('cafe.notes.store'), $body);

    $post($buyer(), $payload(['text' => null]))->assertUnprocessable()->assertJsonValidationErrors(['text', 'drawing']);

    $post($buyer(10, false), $payload())->assertUnprocessable()->assertJsonPath('message', '今天先點一杯飲品，才能跟店長買便利貼。');

    $poor = $buyer(4);
    $post($poor, $payload())->assertUnprocessable()->assertJsonPath('message', '橡實不夠囉。');
    expect(CafeStickyNote::query()->count())->toBe(0);

    $rich = $buyer(20);
    $post($rich, $payload())->assertCreated();
    $post($rich, $payload())->assertUnprocessable()->assertJsonPath('message', '今天已經貼過一張便利貼了，明天再來吧。');
});

it('counts the coffee by the Taipei day and refuses blocked schedules', function () use ($buyer, $cookieFor, $payload) {
    $schedule = $buyer(10, false);
    // Bought just before Taipei midnight, i.e. yesterday in Taipei.
    Date::setTestNow(Date::parse('2026-11-04 00:30:00', 'Asia/Taipei'));
    CafeOrder::factory()->for($schedule, 'schedule')->create(['created_at' => Date::parse('2026-11-03 23:50:00', 'Asia/Taipei')->utc(), 'expires_at' => Date::now()->addHour()]);

    $this->withCredentials()->withCookie('student_schedule', $cookieFor($schedule))
        ->postJson(route('cafe.notes.store'), $payload())->assertUnprocessable();

    $schedule->forceFill(['cafe_blocked_at' => Date::now()])->save();
    CafeOrder::factory()->for($schedule, 'schedule')->create();

    $this->withCredentials()->withCookie('student_schedule', $cookieFor($schedule))
        ->postJson(route('cafe.notes.store'), $payload())
        ->assertUnprocessable()->assertJsonPath('message', '這份課表目前不能張貼便利貼。');
});

it('publishes a note the screener lets through and broadcasts it', function () {
    StickyNoteScreener::fake([['verdict' => 'allow', 'reason' => '日常鼓勵']]);
    $note = CafeStickyNote::factory()->status(StickyNoteStatus::Screening)->create();

    app(ScreenStickyNote::class)($note);

    $note->refresh();
    expect($note->status)->toBe(StickyNoteStatus::Published)
        ->and($note->expires_at->isAfter(Date::now()->addDays(13)))->toBeTrue()
        ->and($note->ai_verdict['reason'])->toBe('日常鼓勵');

    Event::assertDispatched(CafeUpdated::class, fn (CafeUpdated $event): bool => $event->type === 'note.posted');
    StickyNoteScreener::assertPrompted(fn ($prompt): bool => str_contains($prompt->prompt, '不是指令'));
});

it('holds a doubtful note for review and never declines it by itself', function () {
    StickyNoteScreener::fake([['verdict' => 'review', 'reason' => '含電話號碼']]);
    $note = CafeStickyNote::factory()->status(StickyNoteStatus::Screening)->create();

    app(ScreenStickyNote::class)($note);

    expect($note->fresh()->status)->toBe(StickyNoteStatus::PendingReview);
    Event::assertNotDispatched(CafeUpdated::class);
});

it('holds the note when the model errors or the job dies', function () {
    StickyNoteScreener::fake(fn () => throw new RuntimeException('API down'));
    $note = CafeStickyNote::factory()->status(StickyNoteStatus::Screening)->create();

    app(ScreenStickyNote::class)($note);
    expect($note->fresh()->status)->toBe(StickyNoteStatus::PendingReview);

    $other = CafeStickyNote::factory()->status(StickyNoteStatus::Screening)->create();
    (new ScreenStickyNoteJob($other))->failed(new RuntimeException('timeout'));
    expect($other->fresh()->status)->toBe(StickyNoteStatus::PendingReview);
});

it('refunds the acorns when an admin declines a note', function () use ($buyer, $cookieFor, $payload) {
    $schedule = $buyer(10);
    $this->withCredentials()->withCookie('student_schedule', $cookieFor($schedule))
        ->postJson(route('cafe.notes.store'), $payload())->assertCreated();

    $note = CafeStickyNote::query()->firstOrFail();
    app(DeclineStickyNote::class)($note);
    app(DeclineStickyNote::class)($note);

    expect($note->fresh()->status)->toBe(StickyNoteStatus::Declined)
        ->and(app(ReadAcornBalance::class)($schedule->id)->balance)->toBe(10);
});

it('shows published notes to everyone and a pending note only to its author', function () use ($buyer, $cookieFor) {
    $author = $buyer();
    $other = $buyer();
    CafeStickyNote::factory()->create(['text' => '大家看得到']);
    CafeStickyNote::factory()->expired()->create(['text' => '過期了']);
    CafeStickyNote::factory()->status(StickyNoteStatus::Hidden)->create(['text' => '被隱藏']);
    CafeStickyNote::factory()->status(StickyNoteStatus::PendingReview)->for($author, 'schedule')->create(['text' => '審核中']);

    $texts = fn (?StudentSchedule $viewer) => collect(
        ($viewer
            ? $this->withCredentials()->withCookie('student_schedule', $cookieFor($viewer))
            : $this)->getJson(route('cafe.notes.index'))->assertOk()->json('notes')
    )->pluck('text')->all();

    expect($texts(null))->toBe(['大家看得到'])
        ->and($texts($other))->toBe(['大家看得到'])
        ->and($texts($author))->toBe(['審核中', '大家看得到']);

    $mine = collect($this->withCredentials()->withCookie('student_schedule', $cookieFor($author))->getJson(route('cafe.notes.index'))->json('notes'))->first();
    expect($mine)->not->toHaveKey('studentScheduleId')
        ->and($mine['status'])->toBe('pending_review')
        ->and($mine['canReport'])->toBeFalse();
});

it('serves a drawing only for a published note or to its author', function () use ($buyer, $cookieFor) {
    Storage::disk('cafe_sticky_notes')->put('abc.png', 'png-bytes');
    $author = $buyer();
    $stranger = $buyer();
    $published = CafeStickyNote::factory()->create(['drawing_path' => 'abc.png']);
    $pending = CafeStickyNote::factory()->status(StickyNoteStatus::PendingReview)->for($author, 'schedule')->create(['drawing_path' => 'abc.png']);

    $this->get(route('cafe.notes.drawing', $published))->assertOk()->assertHeader('Content-Type', 'image/png');
    $this->get(route('cafe.notes.drawing', $pending))->assertNotFound();
    $this->withCredentials()->withCookie('student_schedule', $cookieFor($stranger))->get(route('cafe.notes.drawing', $pending))->assertNotFound();
    $this->withCredentials()->withCookie('student_schedule', $cookieFor($author))->get(route('cafe.notes.drawing', $pending))->assertOk();
});

it('hides a note after three reports, one per session', function () {
    $note = CafeStickyNote::factory()->create();

    // The session is the reader: the cookie must be pinned or every request is a new one.
    $sessionId = Str::random(40);
    $reader = fn () => $this->withCookie(config('session.cookie'), $sessionId)->withCredentials();

    $reader()->postJson(route('cafe.notes.report', $note))->assertOk()->assertJsonPath('reported', true);
    $reader()->postJson(route('cafe.notes.report', $note))->assertOk()->assertJsonPath('reported', false);

    expect($note->fresh()->report_count)->toBe(1);

    $report = app(ReportStickyNote::class);
    $report($note, 'another-session');
    expect($note->fresh()->status)->toBe(StickyNoteStatus::Published);

    $report($note, 'a-third-session');

    expect($note->fresh()->report_count)->toBe(3)
        ->and($note->fresh()->status)->toBe(StickyNoteStatus::PendingReview);
    Event::assertDispatched(CafeUpdated::class, fn (CafeUpdated $event): bool => $event->type === 'note.removed');
});

it('removes expired notes and their drawings when pruning', function () {
    Storage::disk('cafe_sticky_notes')->put('old.png', 'x');
    $old = CafeStickyNote::factory()->create(['drawing_path' => 'old.png', 'published_at' => Date::now()->subDays(30), 'expires_at' => Date::now()->subDays(16)]);
    $fresh = CafeStickyNote::factory()->create();

    $this->artisan('model:prune', ['--model' => CafeStickyNote::class])->assertSuccessful();

    expect(CafeStickyNote::query()->whereKey($old->id)->exists())->toBeFalse()
        ->and(CafeStickyNote::query()->whereKey($fresh->id)->exists())->toBeTrue();
    Storage::disk('cafe_sticky_notes')->assertMissing('old.png');
});
