<?php

use App\Enums\NewsletterReactionType;
use App\Models\NewsletterIssue;
use App\Models\NewsletterReaction;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;

use function Pest\Laravel\flushSession;
use function Pest\Laravel\get;
use function Pest\Laravel\putJson;
use function Pest\Laravel\withCookie;
use function Pest\Laravel\withoutVite;

beforeEach(function () {
    withoutVite();
    config(['newsletter.anchor_date' => '2026-09-21']);
});

/**
 * Feature tests send no cookies on their own, so every request would get a
 * fresh session id. Pinning the session cookie makes several requests one
 * "reader". The JSON helpers only send cookies with `withCredentials()`.
 */
$readerSession = function (): string {
    return Str::random(40);
};

$asReader = function (string $sessionId): void {
    withCookie(config('session.cookie'), $sessionId)->withCredentials();
};

it('counts an issue once per session', function () use ($readerSession, $asReader) {
    $issue = NewsletterIssue::factory()->publishingOn('2026-09-21')->published()->create();
    $sessionId = $readerSession();

    foreach (range(1, 3) as $_) {
        $asReader($sessionId);
        get(route('newsletter.show', $issue->issue_key))->assertSuccessful();
    }

    expect($issue->fresh()->view_count)->toBe(1);

    // The array session driver keeps attributes in memory across requests, so
    // a genuinely different reader needs the store emptied as well.
    flushSession();
    $asReader($readerSession());
    get(route('newsletter.show', $issue->issue_key))
        ->assertInertia(fn (Assert $page) => $page->where('viewModel.viewCount', 2));

    expect($issue->fresh()->view_count)->toBe(2);
});

it('does not count previews of unpublished issues', function () {
    $issue = NewsletterIssue::factory()->publishingOn('2026-09-21')->ready()->create();

    get(route('newsletter.show', $issue->issue_key))->assertNotFound();

    expect($issue->fresh()->view_count)->toBe(0);
});

it('shares the canonical issue URL and the reaction endpoint', function () {
    $issue = NewsletterIssue::factory()->publishingOn('2026-09-21')->published()->create();

    get(route('newsletter.show', $issue->issue_key).'?utm_source=x')
        ->assertInertia(fn (Assert $page) => $page
            ->where('viewModel.shareUrl', route('newsletter.show', $issue->issue_key))
            ->where('viewModel.reactionUrl', route('newsletter.reaction.update', $issue->issue_key)));
});

it('lists every reaction with zero counts before anyone reacts', function () {
    $issue = NewsletterIssue::factory()->publishingOn('2026-09-21')->published()->create();

    get(route('newsletter.show', $issue->issue_key))
        ->assertInertia(fn (Assert $page) => $page
            ->where('viewModel.reactions.mine', null)
            ->has('viewModel.reactions.options', count(NewsletterReactionType::cases()))
            ->where('viewModel.reactions.options.0.key', 'like')
            ->where('viewModel.reactions.options.0.count', 0));
});

it('lets a session pick, switch and withdraw its one reaction', function () use ($readerSession, $asReader) {
    $issue = NewsletterIssue::factory()->publishingOn('2026-09-21')->published()->create();
    $sessionId = $readerSession();

    $asReader($sessionId);
    putJson(route('newsletter.reaction.update', $issue->issue_key), ['reaction' => 'like'])
        ->assertSuccessful()
        ->assertJsonPath('reactions.mine', 'like')
        ->assertJsonPath('reactions.options.0.count', 1);

    $asReader($sessionId);
    putJson(route('newsletter.reaction.update', $issue->issue_key), ['reaction' => 'love'])
        ->assertJsonPath('reactions.mine', 'love')
        ->assertJsonPath('reactions.options.0.count', 0)
        ->assertJsonPath('reactions.options.1.count', 1);

    expect(NewsletterReaction::query()->count())->toBe(1);

    $asReader($sessionId);
    get(route('newsletter.show', $issue->issue_key))
        ->assertInertia(fn (Assert $page) => $page->where('viewModel.reactions.mine', 'love'));

    $asReader($sessionId);
    putJson(route('newsletter.reaction.update', $issue->issue_key), ['reaction' => null])
        ->assertJsonPath('reactions.mine', null)
        ->assertJsonPath('reactions.options.1.count', 0);

    expect(NewsletterReaction::query()->count())->toBe(0);
});

it('adds up reactions from different sessions', function () use ($readerSession, $asReader) {
    $issue = NewsletterIssue::factory()->publishingOn('2026-09-21')->published()->create();

    foreach (['like', 'like', 'helpful'] as $reaction) {
        $asReader($readerSession());
        putJson(route('newsletter.reaction.update', $issue->issue_key), ['reaction' => $reaction])->assertSuccessful();
    }

    $asReader($readerSession());
    get(route('newsletter.show', $issue->issue_key))
        ->assertInertia(fn (Assert $page) => $page
            ->where('viewModel.reactions.mine', null)
            ->where('viewModel.reactions.options.0.count', 2)
            ->where('viewModel.reactions.options.2.count', 1));
});

it('keeps reactions to each issue separate', function () use ($readerSession, $asReader) {
    $first = NewsletterIssue::factory()->publishingOn('2026-09-21')->published()->create();
    $second = NewsletterIssue::factory()->publishingOn('2026-10-05')->published()->create();
    $sessionId = $readerSession();

    $asReader($sessionId);
    putJson(route('newsletter.reaction.update', $first->issue_key), ['reaction' => 'like'])->assertSuccessful();

    $asReader($sessionId);
    get(route('newsletter.show', $second->issue_key))
        ->assertInertia(fn (Assert $page) => $page
            ->where('viewModel.reactions.mine', null)
            ->where('viewModel.reactions.options.0.count', 0));
});

it('rejects unknown reactions and a missing reaction field', function (array $payload) {
    $issue = NewsletterIssue::factory()->publishingOn('2026-09-21')->published()->create();

    putJson(route('newsletter.reaction.update', $issue->issue_key), $payload)
        ->assertUnprocessable()
        ->assertJsonValidationErrors('reaction');
})->with([
    'unknown' => [['reaction' => 'angry']],
    'missing' => [[]],
]);

it('does not take reactions on unpublished or missing issues', function () {
    $draft = NewsletterIssue::factory()->publishingOn('2026-09-21')->draft()->create();

    putJson(route('newsletter.reaction.update', $draft->issue_key), ['reaction' => 'like'])->assertNotFound();
    putJson(route('newsletter.reaction.update', '2030-W01'), ['reaction' => 'like'])->assertNotFound();

    expect(NewsletterReaction::query()->count())->toBe(0);
});

it('does not keep the raw session id next to the reaction', function () use ($readerSession, $asReader) {
    $issue = NewsletterIssue::factory()->publishingOn('2026-09-21')->published()->create();
    $sessionId = $readerSession();

    $asReader($sessionId);
    putJson(route('newsletter.reaction.update', $issue->issue_key), ['reaction' => 'like'])->assertSuccessful();

    expect(NewsletterReaction::query()->value('session_hash'))
        ->toBe(hash('sha256', $sessionId));
});
